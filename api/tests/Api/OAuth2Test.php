<?php

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\User\Group;
use App\Entity\User\Role;
use App\Entity\User\User;
use App\Enum\RoleEnum;
use Doctrine\ORM\EntityManagerInterface;
use League\Bundle\OAuth2ServerBundle\Manager\ClientManagerInterface;
use League\Bundle\OAuth2ServerBundle\Model\Client;
use League\Bundle\OAuth2ServerBundle\ValueObject\Grant;

class OAuth2Test extends ApiTestCase
{
    private function createAdminUser(): User
    {
        $container = static::getContainer();
        /** @var EntityManagerInterface $em */
        $em = $container->get('doctrine.orm.entity_manager');

        $roleAdmin = new Role();
        $roleAdmin->setCode(RoleEnum::ROLE_ADMIN->value);
        $em->persist($roleAdmin);

        $groupAdmin = new Group();
        $groupAdmin->setName('Admin Group');
        $groupAdmin->setSlug('admin-group-'.uniqid());
        $groupAdmin->setRoles([$roleAdmin]);
        $em->persist($groupAdmin);

        $user = new User();
        $user->setUsername('admin_'.uniqid());
        $user->setEmail('admin_'.uniqid().'@example.com');
        $user->setPassword(password_hash('password123', PASSWORD_BCRYPT));
        $user->addGroup($groupAdmin);
        $em->persist($user);
        $em->flush();

        return $user;
    }

    public function testOAuth2ClientCredentialsGrant(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        /** @var ClientManagerInterface $clientManager */
        $clientManager = $container->get(ClientManagerInterface::class);

        $clientId = 'test_client_'.uniqid();
        $clientSecret = 'test_secret_'.uniqid();

        $oauthClient = new Client('Test Client', $clientId, $clientSecret);
        $oauthClient->setGrants(
            new Grant('client_credentials'),
            new Grant('password'),
            new Grant('refresh_token')
        );
        $oauthClient->setActive(true);
        $clientManager->save($oauthClient);

        $client->getKernelBrowser()->request('POST', '/token', [
            'grant_type' => 'client_credentials',
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
        ]);

        $this->assertResponseStatusCodeSame(200);
        $response = $client->getKernelBrowser()->getResponse();
        $data = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('access_token', $data);
        $this->assertArrayHasKey('token_type', $data);
        $this->assertSame('Bearer', $data['token_type']);
    }

    public function testOAuth2PasswordGrantAndBearerTokenAccess(): void
    {
        $tokenClient = static::createClient();
        $container = static::getContainer();
        /** @var ClientManagerInterface $clientManager */
        $clientManager = $container->get(ClientManagerInterface::class);

        $clientId = 'test_client_'.uniqid();
        $clientSecret = 'test_secret_'.uniqid();

        $oauthClient = new Client('Test Client', $clientId, $clientSecret);
        $oauthClient->setGrants(
            new Grant('password'),
            new Grant('client_credentials')
        );
        $oauthClient->setActive(true);
        $clientManager->save($oauthClient);

        // Create Admin User
        $admin = $this->createAdminUser();

        // 1. Obtain Access Token via Password Grant for Admin User
        $tokenClient->getKernelBrowser()->request('POST', '/token', [
            'grant_type' => 'password',
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'username' => $admin->getUsername(),
            'password' => 'password123',
        ]);

        $this->assertResponseStatusCodeSame(200);
        $response = $tokenClient->getKernelBrowser()->getResponse();
        $data = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('access_token', $data);
        $accessToken = $data['access_token'];

        // 2. Request protected endpoint using the User Bearer Token
        $apiClient = static::createClient();
        $apiClient->request('POST', '/tags', [
            'headers' => [
                'Authorization' => 'Bearer '.$accessToken,
                'Content-Type' => 'application/ld+json',
            ],
            'json' => [
                'name' => 'OAuth2 Admin Tag',
                'slug' => 'oauth2-admin-tag-'.uniqid(),
                'entityType' => 'quest',
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains([
            'name' => 'OAuth2 Admin Tag',
        ]);
    }
}
