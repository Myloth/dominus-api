<?php

namespace App\Factory\User;

use App\Entity\User\User;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<User>
 */
final class UserFactory extends PersistentObjectFactory
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

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
            'password' => 'password',
            'updatedAt' => self::faker()->dateTime(),
            'username' => self::faker()->userName(),
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
            ->afterInstantiate(function (User $user): void {
                if ($user->getPassword()) {
                    $user->setPassword($this->passwordHasher->hashPassword($user, $user->getPassword()));
                }
            })
        ;
    }
}
