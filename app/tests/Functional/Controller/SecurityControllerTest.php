<?php

namespace App\Tests\Functional\Controller\Front;

use App\DataFixtures\UserFixtures;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class SecurityControllerTest extends WebTestCase
{
    public function testLoginPageDisplaysTheForm(): void
    {
        $client = static::createClient();
        $client->request('GET', '/login');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('input[name="_username"]');
        $this->assertSelectorExists('input[name="_password"]');
    }

    public function testProtectedPageRedirectsToLogin(): void
    {
        $client = static::createClient();
        $client->request('GET', '/dashboard/admin/users');

        $this->assertResponseRedirects('/login');
    }

    public function testSuccessfulLoginRedirectsToHome(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/login');
        $form = $crawler->filter('form')->form([
            '_username' => 'admin01@example.com',
            '_password' => UserFixtures::PASSWORD,
        ]);
        $client->submit($form);

        $this->assertResponseRedirects('/');
    }

    public function testLoginWithWrongPasswordRedirectsBackToLogin(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/login');
        $form = $crawler->filter('form')->form([
            '_username' => 'admin@example.com',
            '_password' => 'wrong-password',
        ]);
        $client->submit($form);

        $this->assertResponseRedirects('/login');
    }
}