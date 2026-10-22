<?php

namespace App\Tests\Functional\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class AuthTest extends WebTestCase
{
    public function testLoginWithoutCredentialsReturnsJsonError(): void
    {
        $client = static::createClient();
        $client->request(
            'POST',
            '/api/auth',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: '{}',
        );

        $this->assertResponseStatusCodeSame(400);
        $this->assertJson($client->getResponse()->getContent());
    }

    public function testProfileRejectsAnInvalidToken(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/users/profile', server: [
            'HTTP_AUTHORIZATION' => 'Bearer not-a-real-token',
        ]);

        $this->assertResponseStatusCodeSame(401);
    }
}
