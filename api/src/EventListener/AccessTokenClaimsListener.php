<?php

namespace App\EventListener;

use App\Repository\UserRepository;
use League\Bundle\OAuth2ServerBundle\Event\AccessTokenExtraClaimsResolveEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: 'league.oauth2_server.event.access_token_extra_claims_resolve', method: 'onExtraClaimsResolve')]
final class AccessTokenClaimsListener
{
    public function __construct(
        private readonly UserRepository $userRepository,
    ) {
    }

    public function onExtraClaimsResolve(AccessTokenExtraClaimsResolveEvent $event): void
    {
        $userIdentifier = $event->getUserIdentifier();
        if (null === $userIdentifier) {
            return;
        }

        $user = $this->userRepository->findOneBy(['username' => $userIdentifier])
            ?? $this->userRepository->findOneBy(['email' => $userIdentifier]);

        if (null === $user) {
            return;
        }

        $extraClaims = [
            'roles' => array_values($user->getRoles()),
        ];

        if ($user->getEmail()) {
            $extraClaims['email'] = $user->getEmail();
        }

        if ($user->getId()) {
            $extraClaims['user_id'] = $user->getId();
        }

        $event->setExtraClaims(array_merge($event->getExtraClaims(), $extraClaims));
    }
}
