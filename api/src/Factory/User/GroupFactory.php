<?php

namespace App\Factory\User;

use App\Entity\User\Group;
use App\Enum\RoleEnum;
use Doctrine\Common\Collections\Criteria;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @extends PersistentObjectFactory<Group>
 */
final class GroupFactory extends PersistentObjectFactory
{
    #[\Override]
    public static function class(): string
    {
        return Group::class;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @todo add your default values here
     */
    #[\Override]
    protected function defaults(): array|callable
    {
        return [
            'name' => self::faker()->word(50),
            'slug' => self::faker()->text(50),
            'roles' => new ArrayCollection(RoleFactory::randomSet(
                random_int(1, 2),
                ['code' => [RoleEnum::ROLE_ADMIN->value, RoleEnum::ROLE_MODERATOR->value]]
            )),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    #[\Override]
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(Group $group): void {})
        ;
    }
}
