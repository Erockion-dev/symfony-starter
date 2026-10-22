<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Enum\Role;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class UserFixtures extends Fixture
{
    public const PASSWORD = 'password123';

    public function __construct(
        private readonly UserPasswordHasherInterface $hasher,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $accounts = [
            ['admin01@example.com', 'Admin', 'One', [Role::Admin->value]],
            ['operator01@example.com', 'Operator', 'One', [Role::Operator->value]],
            ['user01@example.com', 'User', 'One', [Role::User->value]],
        ];

        foreach ($accounts as [$email, $firstname, $lastname, $roles]) {
            $user = new User();
            $user->setEmail($email);
            $user->setFirstname($firstname);
            $user->setLastname($lastname);
            $user->setLastname('Test');
            $user->setRoles($roles);
            $user->setIsActive(true);
            $user->setPassword($this->hasher->hashPassword($user, self::PASSWORD));

            $manager->persist($user);
        }

        $manager->flush();
    }
}
