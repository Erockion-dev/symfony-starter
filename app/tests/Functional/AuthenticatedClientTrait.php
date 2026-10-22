<?php

namespace App\Tests\Functional;

use App\Repository\UserRepository;
use Psr\Container\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

trait AuthenticatedClientTrait
{
    abstract protected static function createClient(array $options = [], array $server = []): KernelBrowser;

    abstract protected static function getContainer(): ContainerInterface;

    private function createClientLoggedInAs(string $email): KernelBrowser
    {
        $client = static::createClient();
        $user = static::getContainer()->get(UserRepository::class)->findOneBy(['email' => $email]);
        $client->loginUser($user);

        return $client;
    }
}
