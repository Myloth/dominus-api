<?php

namespace App\EventListener;

use App\Repository\UserRepository;
use League\Bundle\OAuth2ServerBundle\Event\UserResolveEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsEventListener(event: 'league.oauth2_server.event.user_resolve', method: 'onUserResolve')]
final class UserResolveListener
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function onUserResolve(UserResolveEvent $event): void
    {
        $identifier = $event->getUsername();
        $user = $this->userRepository->findOneBy(['username' => $identifier])
            ?? $this->userRepository->findOneBy(['email' => $identifier]);

        if (null === $user) {
            return;
        }

        if ($this->passwordHasher->isPasswordValid($user, $event->getPassword())) {
            $event->setUser($user);
        }
    }
}
