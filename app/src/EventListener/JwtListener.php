<?php

namespace App\EventListener;

use App\Entity\User;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

final class JwtListener
{
    #[AsEventListener(event: Events::JWT_CREATED)]
    public function onJwtCreated(JWTCreatedEvent $event): void
    {
        $user = $event->getUser();
        if (!$user instanceof User) {
            return;
        }

        $payload = $event->getData();
        $payload['sub'] = (string) $user->getId();
        $payload['roles'] = array_map(
            fn (string $role) => strtolower(str_replace('ROLE_', '', $role)),
            $user->getRoles(),
        );
        $event->setData($payload);
    }

    #[AsEventListener(event: Events::AUTHENTICATION_SUCCESS)]
    public function onAuthenticationSuccess(AuthenticationSuccessEvent $event): void
    {
        $event->setData(['access_token' => $event->getData()['token']]);
    }
}
