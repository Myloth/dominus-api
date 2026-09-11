<?php

namespace App\Story;

use App\Factory\User\GroupFactory;
use App\Factory\User\RoleFactory;
use App\Factory\User\UserFactory;
use League\Bundle\OAuth2ServerBundle\Manager\ClientManagerInterface;
use League\Bundle\OAuth2ServerBundle\Model\Client;
use League\Bundle\OAuth2ServerBundle\ValueObject\Grant;
use League\Bundle\OAuth2ServerBundle\ValueObject\Scope;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Zenstruck\Foundry\Attribute\AsFixture;
use Zenstruck\Foundry\Story;

#[AsFixture(name: 'default', groups: ['all'])]
final class DefaultUserStory extends Story
{
    public function __construct(
        private readonly ClientManagerInterface $clientManager,
        #[Autowire(service: 'league.oauth2_server.password_hasher')]
        private readonly ?PasswordHasherInterface $clientPasswordHasher = null,
    ) {
    }

    public function build(): void
    {
        $slugger = new AsciiSlugger();
        self::addToPool('roles', RoleFactory::createSpecific());

        $groups = ['Administrateurs', 'Modérateurs'];
        foreach ($groups as $groupName) {
            $this->addToPool('groups', GroupFactory::createOne(['name' => $groupName, 'slug' => $slugger->slug($groupName)]));
        }

        $superAdminGroup = GroupFactory::createOne(['name' => 'SuperAdmin', 'slug' => $slugger->slug('superadmin')]);
        UserFactory::createMany(10);

        UserFactory::createOne([
            'username' => 'Myloth',
            'password' => 'Myloth',
            'email' => 'myloth@example.com',
            'groups' => [$superAdminGroup],
        ]);

        $this->createOAuthClient();
    }

    private function createOAuthClient(): void
    {
        $identifier = 'dominus-client';
        if (null !== $this->clientManager->find($identifier)) {
            return;
        }

        $secret = 'dominus-secret';
        $hashedSecret = $this->clientPasswordHasher ? $this->clientPasswordHasher->hash($secret) : $secret;

        $client = new Client('Dominus Client', $identifier, $hashedSecret);
        $client->setActive(true);
        $client->setGrants(
            new Grant('password'),
            new Grant('refresh_token'),
            new Grant('client_credentials')
        );
        $client->setScopes(
            new Scope('game'),
            new Scope('administration')
        );

        $this->clientManager->save($client);
    }
}
