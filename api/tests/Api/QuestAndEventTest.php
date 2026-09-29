<?php

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\User\Group;
use App\Entity\User\Role;
use App\Entity\User\User;
use App\Enum\RoleEnum;
use Doctrine\ORM\EntityManagerInterface;

class QuestAndEventTest extends ApiTestCase
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

    public function testPublicReadAccess(): void
    {
        $client = static::createClient();

        // GET events and quests without authentication
        $client->request('GET', '/events');
        $this->assertResponseStatusCodeSame(200);

        $client->request('GET', '/quests');
        $this->assertResponseStatusCodeSame(200);
    }

    public function testEventCrudAndValidation(): void
    {
        $client = static::createClient();
        $admin = $this->createAdminUser();

        // Date validation error: endDate before startDate
        $client->request('POST', '/events', [
            'auth_basic' => [$admin->getUsername(), 'password123'],
            'json' => [
                'name' => 'Invalid Event',
                'startDate' => '2026-06-10T10:00:00Z',
                'endDate' => '2026-06-01T10:00:00Z',
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);
        $this->assertResponseStatusCodeSame(422);

        // Successful Event creation
        $response = $client->request('POST', '/events', [
            'auth_basic' => [$admin->getUsername(), 'password123'],
            'json' => [
                'name' => 'Summer Festival',
                'startDate' => '2026-06-01T10:00:00Z',
                'endDate' => '2026-06-10T10:00:00Z',
                'active' => true,
                'description' => 'Annual summer festival in the capital.',
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);
        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains([
            'name' => 'Summer Festival',
            'active' => true,
            'description' => 'Annual summer festival in the capital.',
        ]);

        $eventId = $response->toArray()['id'];

        // GET Event item (public)
        $client->request('GET', '/events/'.$eventId);
        $this->assertResponseStatusCodeSame(200);

        // GET Event collection (public)
        $client->request('GET', '/events');
        $this->assertResponseStatusCodeSame(200);
    }

    public function testQuestCrudAndRelationsAndFilter(): void
    {
        $client = static::createClient();
        $admin = $this->createAdminUser();

        // 1. Create a Tag to link
        $tagResponse = $client->request('POST', '/tags', [
            'auth_basic' => [$admin->getUsername(), 'password123'],
            'json' => [
                'name' => 'Main Quest Tag',
                'slug' => 'main-quest-tag-'.uniqid(),
                'entityType' => 'quest',
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);
        $this->assertResponseStatusCodeSame(201);
        $tagIri = $tagResponse->toArray()['@id'];
        $tagSlug = $tagResponse->toArray()['slug'];

        // 2. Create Event to link
        $eventResponse = $client->request('POST', '/events', [
            'auth_basic' => [$admin->getUsername(), 'password123'],
            'json' => [
                'name' => 'Quest Event '.uniqid(),
                'active' => true,
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);
        $this->assertResponseStatusCodeSame(201);
        $eventIri = $eventResponse->toArray()['@id'];

        // 3. Create Prerequisite Quest
        $prereqName = 'Prerequisite Quest '.uniqid();
        $prereqResponse = $client->request('POST', '/quests', [
            'auth_basic' => [$admin->getUsername(), 'password123'],
            'json' => [
                'name' => $prereqName,
                'nivGuildeReq' => 1,
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);
        $this->assertResponseStatusCodeSame(201);
        $prereqIri = $prereqResponse->toArray()['@id'];

        // 4. Create Main Quest linked to Prereq, Event, and Tag
        $questName = 'The Great Journey '.uniqid();
        $questResponse = $client->request('POST', '/quests', [
            'auth_basic' => [$admin->getUsername(), 'password123'],
            'json' => [
                'name' => $questName,
                'nivGuildeReq' => 5,
                'preReqQuest' => $prereqIri,
                'event' => $eventIri,
                'objectives' => ['defeat_boss' => 1, 'collect_herbs' => 10],
                'rewards' => ['gold' => 500, 'xp' => 1200],
                'unlockLore' => 'A ancient scroll was discovered...',
                'tags' => [$tagIri],
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);
        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains([
            'name' => $questName,
            'nivGuildeReq' => 5,
            'objectives' => ['defeat_boss' => 1, 'collect_herbs' => 10],
            'rewards' => ['gold' => 500, 'xp' => 1200],
            'unlockLore' => 'A ancient scroll was discovered...',
        ]);

        $questId = $questResponse->toArray()['id'];

        // 5. Test Public Filtering by Tag slug
        $client->request('GET', '/quests?tags.slug='.$tagSlug);
        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonContains([
            'totalItems' => 1,
        ]);

        // 6. Test Duplicate Quest Name Validation
        $client->request('POST', '/quests', [
            'auth_basic' => [$admin->getUsername(), 'password123'],
            'json' => [
                'name' => $questName,
            ],
            'headers' => [
                'Content-Type' => 'application/ld+json',
            ],
        ]);
        $this->assertResponseStatusCodeSame(422);

        // 7. Test PUT and PATCH on Quest
        $client->request('PATCH', '/quests/'.$questId, [
            'auth_basic' => [$admin->getUsername(), 'password123'],
            'json' => [
                'nivGuildeReq' => 10,
            ],
            'headers' => [
                'Content-Type' => 'application/merge-patch+json',
            ],
        ]);
        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonContains([
            'nivGuildeReq' => 10,
        ]);
    }
}
