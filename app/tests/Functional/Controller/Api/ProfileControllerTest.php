<?php

namespace App\Tests\Functional\Api;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ProfileControllerTest extends WebTestCase
{
    public function testProfileRequiresAToken(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/users/profile');

        $this->assertResponseStatusCodeSame(401);
    }
}
