<?php

namespace App\Tests\Functional\Controller\Backoffice;

use App\Repository\UserRepository;
use App\Tests\Functional\AuthenticatedClientTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class PagesTest extends WebTestCase
{
    use AuthenticatedClientTrait;

    public static function adminPages(): iterable
    {
        yield 'dashboard' => ['/dashboard'];
        yield 'admin dashboard' => ['/dashboard/admin'];
        yield 'user list' => ['/dashboard/admin/users'];
        yield 'new user' => ['/dashboard/admin/users/new'];
    }

    #[DataProvider('adminPages')]
    public function testAdminCanSeeThePage(string $url): void
    {
        $client = $this->createClientLoggedInAs('admin01@example.com');
        $client->request('GET', $url);

        $this->assertResponseIsSuccessful();
    }

    public function testAdminCanSeeUserDetailAndEditPages(): void
    {
        $client = $this->createClientLoggedInAs('admin01@example.com');
        $id = static::getContainer()->get(UserRepository::class)
            ->findOneBy(['email' => 'user01@example.com'])
            ->getId();

        $client->request('GET', '/dashboard/admin/users/'.$id);
        $this->assertResponseIsSuccessful();

        $client->request('GET', '/dashboard/admin/users/'.$id.'/edit');
        $this->assertResponseIsSuccessful();
    }
}
