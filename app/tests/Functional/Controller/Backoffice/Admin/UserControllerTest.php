<?php

namespace App\Tests\Functional\Controller\Backoffice\Admin;

use App\Repository\UserRepository;
use App\Tests\Functional\AuthenticatedClientTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class UserControllerTest extends WebTestCase
{
    use AuthenticatedClientTrait;

    public function testAdminCanCreateAUser(): void
    {
        $client = $this->createClientLoggedInAs('admin01@example.com');

        $crawler = $client->request('GET', '/dashboard/admin/users/new');
        $form = $crawler->filter('form')->form([
            'user[email]' => 'new-user@example.com',
            'user[firstname]' => 'New',
            'user[lastname]' => 'User',
            'user[plainPassword][first]' => 'password123',
            'user[plainPassword][second]' => 'password123',
        ]);
        $client->submit($form);

        $this->assertResponseRedirects('/dashboard/admin/users');

        $user = static::getContainer()->get(UserRepository::class)
            ->findOneBy(['email' => 'new-user@example.com']);

        $this->assertNotNull($user);
        $this->assertSame('New', $user->getFirstname());
        $this->assertNotSame('password123', $user->getPassword()); // stocké hashé
    }

    public function testAdminCanUpdateUser(): void
    {
        $client = $this->createClientLoggedInAs('admin01@example.com');

        $id = static::getContainer()->get(UserRepository::class)
            ->findOneBy(['email' => 'user01@example.com'])
            ->getId();

        $crawler = $client->request('GET', '/dashboard/admin/users/'.$id.'/edit');
        $form = $crawler->filter('form')->form([
            'user[firstname]' => 'Update',
        ]);
        $client->submit($form);

        $this->assertResponseRedirects('/dashboard/admin/users');

        $user = static::getContainer()->get(UserRepository::class)
            ->findOneBy(['email' => 'user01@example.com']);

        $this->assertNotNull($user);
        $this->assertSame('Update', $user->getFirstname());
    }

    public function testAdminCanSoftDelete(): void
    {
        $client = $this->createClientLoggedInAs('admin01@example.com');

        $id = static::getContainer()->get(UserRepository::class)
            ->findOneBy(['email' => 'user01@example.com'])
            ->getId();
        
        $crawler = $client->request('GET', '/dashboard/admin/users');

        $row = $crawler->filter('tr')->reduce(
            fn ($node) => str_contains($node->text(), 'user01@example.com')
        );

        $form = $row->selectButton('Delete')->form();

        $client->submit($form);

        $this->assertResponseRedirects('/dashboard/admin/users');

        $user = static::getContainer()->get(UserRepository::class)
            ->findOneBy(['email' => 'user01@example.com']);

        $this->assertNotNull($user);
        $this->assertNotNull($user->getDeletedAt());

    }
}
