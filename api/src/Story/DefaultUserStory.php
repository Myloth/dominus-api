<?php

namespace App\Story;

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
        RoleFactory::createMany(10);
        GroupFactory::createMany(10);
        UserFactory::createMany(10);
    }
}
