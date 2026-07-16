<?php

namespace App\Factory\User;

use App\Entity\User\User;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @extends PersistentObjectFactory<User>
 */
final class UserFactory extends PersistentObjectFactory
{
    #[\Override]
    public static function class(): string
    {
        return User::class;
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
            'createdAt' => self::faker()->dateTime(),
            'email' => self::faker()->email(),
            'password' => self::faker()->text(),
            'updatedAt' => self::faker()->dateTime(),
            'username' => self::faker()->username(),
            'groups' => new ArrayCollection(GroupFactory::randomRange(1, 2)),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    #[\Override]
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(User $user): void {})
        ;
    }
}
