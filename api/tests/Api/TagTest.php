<?php

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\EntityType;
use App\Entity\User\Group;
use App\Entity\User\Role;
use App\Entity\User\User;
use App\Enum\EntityTypeEnum;
use App\Enum\RoleEnum;
use Doctrine\ORM\EntityManagerInterface;

class TagTest extends ApiTestCase
{
    private function getOrCreateEntityType(EntityTypeEnum $typeEnum): EntityType
    {
        $container = static::getContainer();
        /** @var EntityManagerInterface $em */
        $em = $container->get('doctrine.orm.entity_manager');

        $entityType = $em->getRepository(EntityType::class)->findOneBy(['slug' => $typeEnum->value]);
        if (null === $entityType) {
            $entityType = new EntityType();
            $entityType->setName($typeEnum->label());
            $entityType->setSlug($typeEnum->value);
            $em->persist($entityType);
            $em->flush();
        }

        return $entityType;
    }

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

    public function testPublicReadAccess(): void
    {
        $client = static::createClient();
        $client->request('GET', '/tags');
        $this->assertResponseStatusCodeSame(200);
    }

    public function testEntityTypeReadOnlyOperations(): void
    {
        $client = static::createClient();
        $admin = $this->createAdminUser();
        $questType = $this->getOrCreateEntityType(EntityTypeEnum::QUEST);

        $client->request('GET', '/entity_types');
        $this->assertResponseStatusCodeSame(200);

        $client->request('GET', '/entity_types/'.$questType->getId());
        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonContains([
            'id' => $questType->getId(),
            'name' => EntityTypeEnum::QUEST->label(),
            'slug' => EntityTypeEnum::QUEST->value,
        ]);

        // POST is not allowed on EntityType
        $client->request('POST', '/entity_types', [
            'auth_basic' => [$admin->getUsername(), 'password123'],
            'json' => [
                'name' => 'Custom',
                'slug' => 'custom',
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);
        $this->assertResponseStatusCodeSame(405);
    }

    public function testUnauthorizedWriteAccess(): void
    {
        $client = static::createClient();
        $questType = $this->getOrCreateEntityType(EntityTypeEnum::QUEST);

        $client->request('POST', '/tags', [
            'json' => [
                'name' => 'Unauthorized Tag',
                'entityTypes' => ['/entity_types/'.$questType->getId()],
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);
        $this->assertResponseStatusCodeSame(401);
    }

    public function testForbiddenWriteAccessForNonAdmin(): void
    {
        $client = static::createClient();
        $user = $this->createRegularUser();
        $questType = $this->getOrCreateEntityType(EntityTypeEnum::QUEST);

        // GET allowed for non-admin
        $client->request('GET', '/tags', [
            'auth_basic' => [$user->getUsername(), 'password123'],
        ]);
        $this->assertResponseStatusCodeSame(200);

        // POST forbidden for non-admin
        $client->request('POST', '/tags', [
            'auth_basic' => [$user->getUsername(), 'password123'],
            'json' => [
                'name' => 'Forbidden Tag',
                'entityTypes' => ['/entity_types/'.$questType->getId()],
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);
        $this->assertResponseStatusCodeSame(403);
    }

    public function testCreateAndReadTagAsAdmin(): void
    {
        $client = static::createClient();
        $admin = $this->createAdminUser();
        $questType = $this->getOrCreateEntityType(EntityTypeEnum::QUEST);
        $dungeonType = $this->getOrCreateEntityType(EntityTypeEnum::DUNGEON);
        $questTypeIri = '/entity_types/'.$questType->getId();
        $dungeonTypeIri = '/entity_types/'.$dungeonType->getId();

        $uniqid = uniqid();
        $expectedSlug = 'main-quest-'.strtolower($uniqid);

        // Submit tag with multiple entityTypes and a custom slug that should be ignored and auto-generated from name
        $response = $client->request('POST', '/tags', [
            'auth_basic' => [$admin->getUsername(), 'password123'],
            'json' => [
                'name' => 'Main Quest '.$uniqid,
                'slug' => 'custom-slug-should-be-ignored',
                'entityTypes' => [$questTypeIri, $dungeonTypeIri],
                'category' => 'story',
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains([
            'name' => 'Main Quest '.$uniqid,
            'slug' => $expectedSlug,
            'entityTypes' => [
                [
                    '@id' => $questTypeIri,
                    'id' => $questType->getId(),
                    'name' => EntityTypeEnum::QUEST->label(),
                    'slug' => EntityTypeEnum::QUEST->value,
                ],
                [
                    '@id' => $dungeonTypeIri,
                    'id' => $dungeonType->getId(),
                    'name' => EntityTypeEnum::DUNGEON->label(),
                    'slug' => EntityTypeEnum::DUNGEON->value,
                ],
            ],
            'category' => 'story',
        ]);

        $data = $response->toArray();
        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('slug', $data);
        $this->assertArrayHasKey('createdAt', $data);
        $this->assertSame($expectedSlug, $data['slug']);
        $tagId = $data['id'];

        // GET Collection (listing) - slug must be returned
        $listResponse = $client->request('GET', '/tags?name=Main Quest '.$uniqid);
        $this->assertResponseStatusCodeSame(200);
        $listData = $listResponse->toArray();
        $this->assertArrayHasKey('member', $listData);
        $this->assertNotEmpty($listData['member']);
        $this->assertSame($expectedSlug, $listData['member'][0]['slug']);

        // GET Item (detail) - slug must be returned
        $client->request('GET', '/tags/'.$tagId);
        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonContains([
            'id' => $tagId,
            'name' => 'Main Quest '.$uniqid,
            'slug' => $expectedSlug,
        ]);
    }

    public function testUpdateTagAsAdmin(): void
    {
        $client = static::createClient();
        $admin = $this->createAdminUser();
        $pnjType = $this->getOrCreateEntityType(EntityTypeEnum::PNJ);
        $pnjTypeIri = '/entity_types/'.$pnjType->getId();

        $uniqid = uniqid();
        $originalSlug = 'original-name-'.strtolower($uniqid);

        $response = $client->request('POST', '/tags', [
            'auth_basic' => [$admin->getUsername(), 'password123'],
            'json' => [
                'name' => 'Original Name '.$uniqid,
                'entityTypes' => [$pnjTypeIri],
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $tagId = $response->toArray()['id'];
        $this->assertSame($originalSlug, $response->toArray()['slug']);

        // PUT update - slug must NOT change even if submitted or name changed
        $client->request('PUT', '/tags/'.$tagId, [
            'auth_basic' => [$admin->getUsername(), 'password123'],
            'json' => [
                'name' => 'Updated Name '.$uniqid,
                'slug' => 'attempted-new-slug',
                'entityTypes' => [$pnjTypeIri],
                'category' => 'boss',
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);
        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonContains([
            'name' => 'Updated Name '.$uniqid,
            'slug' => $originalSlug,
            'category' => 'boss',
        ]);

        // PATCH update - slug must remain definitive
        $client->request('PATCH', '/tags/'.$tagId, [
            'auth_basic' => [$admin->getUsername(), 'password123'],
            'json' => [
                'name' => 'Patched Name '.$uniqid,
                'slug' => 'another-attempted-slug',
            ],
            'headers' => [
                'Content-Type' => 'application/merge-patch+json',
            ],
        ]);
        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonContains([
            'name' => 'Patched Name '.$uniqid,
            'slug' => $originalSlug,
        ]);

        // GET Item detail confirms slug is definitively unchanged
        $client->request('GET', '/tags/'.$tagId);
        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonContains([
            'name' => 'Patched Name '.$uniqid,
            'slug' => $originalSlug,
        ]);
    }

    public function testValidationErrors(): void
    {
        $client = static::createClient();
        $admin = $this->createAdminUser();
        $dungeonType = $this->getOrCreateEntityType(EntityTypeEnum::DUNGEON);
        $dungeonTypeIri = '/entity_types/'.$dungeonType->getId();

        // Missing required fields (name, entityTypes)
        $client->request('POST', '/tags', [
            'auth_basic' => [$admin->getUsername(), 'password123'],
            'json' => [
                'name' => '',
                'entityTypes' => [],
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);
        $this->assertResponseStatusCodeSame(422);

        // Unique slug validation: two tags with the same name produce the same slug
        $uniqueName = 'Unique Tag Name '.uniqid();

        $client->request('POST', '/tags', [
            'auth_basic' => [$admin->getUsername(), 'password123'],
            'json' => [
                'name' => $uniqueName,
                'entityTypes' => [$dungeonTypeIri],
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);
        $this->assertResponseStatusCodeSame(201);

        $client->request('POST', '/tags', [
            'auth_basic' => [$admin->getUsername(), 'password123'],
            'json' => [
                'name' => $uniqueName,
                'entityTypes' => [$dungeonTypeIri],
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);
        $this->assertResponseStatusCodeSame(422);
    }

    public function testDeleteAllowedAsAdmin(): void
    {
        $client = static::createClient();
        $admin = $this->createAdminUser();
        $gearType = $this->getOrCreateEntityType(EntityTypeEnum::GEAR);

        $response = $client->request('POST', '/tags', [
            'auth_basic' => [$admin->getUsername(), 'password123'],
            'json' => [
                'name' => 'Tag To Delete '.uniqid(),
                'entityTypes' => ['/entity_types/'.$gearType->getId()],
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
        $this->assertResponseStatusCodeSame(204);

        $client->request('GET', '/tags/'.$tagId);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testFilterTags(): void
    {
        $client = static::createClient();
        $admin = $this->createAdminUser();
        $questType = $this->getOrCreateEntityType(EntityTypeEnum::QUEST);
        $pnjType = $this->getOrCreateEntityType(EntityTypeEnum::PNJ);

        $uniqid = uniqid();

        $client->request('POST', '/tags', [
            'auth_basic' => [$admin->getUsername(), 'password123'],
            'json' => [
                'name' => 'Quest Unique Tag '.$uniqid,
                'entityTypes' => ['/entity_types/'.$questType->getId()],
            ],
            'headers' => ['Content-Type' => 'application/ld+json'],
        ]);
        $this->assertResponseStatusCodeSame(201);

        $client->request('POST', '/tags', [
            'auth_basic' => [$admin->getUsername(), 'password123'],
            'json' => [
                'name' => 'PNJ Unique Tag '.$uniqid,
                'entityTypes' => ['/entity_types/'.$pnjType->getId()],
            ],
            'headers' => ['Content-Type' => 'application/ld+json'],
        ]);
        $this->assertResponseStatusCodeSame(201);

        $response = $client->request('GET', '/tags?entityTypes.slug=quest&name=Quest Unique Tag '.$uniqid);
        $this->assertResponseStatusCodeSame(200);
        $data = $response->toArray();
        $this->assertGreaterThanOrEqual(1, count($data['member']));
        foreach ($data['member'] as $member) {
            $slugs = array_column($member['entityTypes'], 'slug');
            $this->assertContains('quest', $slugs);
            $this->assertArrayHasKey('slug', $member);
        }
    }
}
