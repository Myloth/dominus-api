<?php

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\User\Group;
use App\Entity\User\Role;
use App\Entity\User\User;
use App\Enum\RoleEnum;
use Doctrine\ORM\EntityManagerInterface;

class TagTest extends ApiTestCase
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

    private function createRegularUser(): User
    {
        $container = static::getContainer();
        /** @var EntityManagerInterface $em */
        $em = $container->get('doctrine.orm.entity_manager');

        $user = new User();
        $user->setUsername('user_'.uniqid());
        $user->setEmail('user_'.uniqid().'@example.com');
        $user->setPassword(password_hash('password123', PASSWORD_BCRYPT));
        $em->persist($user);
        $em->flush();

        return $user;
    }

    public function testUnauthorizedAccess(): void
    {
        $client = static::createClient();
        $client->request('GET', '/tags');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testForbiddenAccessForNonAdmin(): void
    {
        $client = static::createClient();
        $user = $this->createRegularUser();

        $client->request('GET', '/tags', [
            'auth_basic' => [$user->getUsername(), 'password123'],
        ]);
        $this->assertResponseStatusCodeSame(403);
    }

    public function testCreateAndReadTagAsAdmin(): void
    {
        $client = static::createClient();
        $admin = $this->createAdminUser();

        $slug = 'main-quest-'.uniqid();

        $response = $client->request('POST', '/tags', [
            'auth_basic' => [$admin->getUsername(), 'password123'],
            'json' => [
                'name' => 'Main Quest',
                'slug' => $slug,
                'entityType' => 'quest',
                'category' => 'story',
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains([
            'name' => 'Main Quest',
            'slug' => $slug,
            'entityType' => 'quest',
            'category' => 'story',
        ]);

        $data = $response->toArray();
        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('createdAt', $data);
        $tagId = $data['id'];

        // GET Collection
        $client->request('GET', '/tags', [
            'auth_basic' => [$admin->getUsername(), 'password123'],
        ]);
        $this->assertResponseStatusCodeSame(200);

        // GET Item
        $client->request('GET', '/tags/'.$tagId, [
            'auth_basic' => [$admin->getUsername(), 'password123'],
        ]);
        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonContains([
            'name' => 'Main Quest',
            'slug' => $slug,
        ]);
    }

    public function testUpdateTagAsAdmin(): void
    {
        $client = static::createClient();
        $admin = $this->createAdminUser();

        $originalSlug = 'original-slug-'.uniqid();
        $updatedSlug = 'updated-slug-'.uniqid();

        $response = $client->request('POST', '/tags', [
            'auth_basic' => [$admin->getUsername(), 'password123'],
            'json' => [
                'name' => 'Original Name',
                'slug' => $originalSlug,
                'entityType' => 'pnj',
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $tagId = $response->toArray()['id'];

        // PUT update
        $client->request('PUT', '/tags/'.$tagId, [
            'auth_basic' => [$admin->getUsername(), 'password123'],
            'json' => [
                'name' => 'Updated Name',
                'slug' => $updatedSlug,
                'entityType' => 'pnj',
                'category' => 'boss',
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);
        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonContains([
            'name' => 'Updated Name',
            'slug' => $updatedSlug,
            'category' => 'boss',
        ]);

        // PATCH update
        $client->request('PATCH', '/tags/'.$tagId, [
            'auth_basic' => [$admin->getUsername(), 'password123'],
            'json' => [
                'name' => 'Patched Name',
            ],
            'headers' => [
                'Content-Type' => 'application/merge-patch+json',
            ],
        ]);
        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonContains([
            'name' => 'Patched Name',
        ]);
    }

    public function testValidationErrors(): void
    {
        $client = static::createClient();
        $admin = $this->createAdminUser();

        // Missing required fields
        $client->request('POST', '/tags', [
            'auth_basic' => [$admin->getUsername(), 'password123'],
            'json' => [
                'name' => '',
                'slug' => '',
                'entityType' => '',
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);
        $this->assertResponseStatusCodeSame(422);

        // Unique slug validation
        $uniqueSlug = 'unique-slug-'.uniqid();

        $client->request('POST', '/tags', [
            'auth_basic' => [$admin->getUsername(), 'password123'],
            'json' => [
                'name' => 'Tag 1',
                'slug' => $uniqueSlug,
                'entityType' => 'event',
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);
        $this->assertResponseStatusCodeSame(201);

        $client->request('POST', '/tags', [
            'auth_basic' => [$admin->getUsername(), 'password123'],
            'json' => [
                'name' => 'Tag 2',
                'slug' => $uniqueSlug,
                'entityType' => 'event',
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);
        $this->assertResponseStatusCodeSame(422);
    }

    public function testDeleteNotAllowed(): void
    {
        $client = static::createClient();
        $admin = $this->createAdminUser();

        $slug = 'tag-to-delete-'.uniqid();

        $response = $client->request('POST', '/tags', [
            'auth_basic' => [$admin->getUsername(), 'password123'],
            'json' => [
                'name' => 'Tag To Delete',
                'slug' => $slug,
                'entityType' => 'equipment',
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);
        $this->assertResponseStatusCodeSame(201);
        $tagId = $response->toArray()['id'];

        $client->request('DELETE', '/tags/'.$tagId, [
            'auth_basic' => [$admin->getUsername(), 'password123'],
        ]);
        $this->assertResponseStatusCodeSame(405);
    }
}
