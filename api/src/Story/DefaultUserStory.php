<?php

namespace App\Story;

use Symfony\Component\String\Slugger\AsciiSlugger;
use Zenstruck\Foundry\Story;
use App\Factory\User\RoleFactory;
use App\Factory\User\GroupFactory;
use App\Factory\User\UserFactory;
use Zenstruck\Foundry\Attribute\AsFixture;

#[AsFixture(name: 'default', groups: ['all'])]
final class DefaultUserStory extends Story
{
    public function build(): void
    {
        $slugger = new AsciiSlugger();
        self::addToPool('roles', RoleFactory::createSpecific());

        $groups = ['Administrateurs', 'Modérateurs'];
        foreach ($groups as $groupName) {
            $this->addToPool('groups', GroupFactory::createOne(['name' => $groupName, 'slug' => $slugger->slug($groupName)]));
        }
        GroupFactory::createOne(['name' => 'SuperAdmin', 'slug' => $slugger->slug('superadmin')]);
        UserFactory::createMany(10);
    }
}
