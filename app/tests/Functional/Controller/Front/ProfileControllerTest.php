<?php

namespace App\Tests\Functional\Controller\Front;

use App\Tests\Functional\AuthenticatedClientTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ProfileControllerTest extends WebTestCase
{
    use AuthenticatedClientTrait;

    public function testProfilePageShowsTheCurrentUser(): void
    {
        $client = $this->createClientLoggedInAs('user01@example.com');
        $client->request('GET', '/profile');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'user01@example.com');
    }

    public function testProfileRedirectsAnonymousVisitorToLogin(): void
    {
        $client = static::createClient();
        $client->request('GET', '/profile');

        $this->assertResponseRedirects('/login');
    }
}
