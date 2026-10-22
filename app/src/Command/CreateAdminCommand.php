<?php

namespace App\Command;

use App\Entity\User;
use App\Enum\Role;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(name: 'app:create-admin', description: 'Create the first admin user')]
final class CreateAdminCommand extends Command
{
    private const EMAIL = 'admin@example.com';
    private const PASSWORD = '123_admin';

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserRepository $users,
        private readonly UserPasswordHasherInterface $hasher,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ($this->users->findOneBy(['email' => self::EMAIL])) {
            $io->warning('The admin already exists.');

            return Command::SUCCESS;
        }

        $user = new User();
        $user->setEmail(self::EMAIL);
        $user->setFirstname('admin');
        $user->setLastname('admin');
        $user->setRoles([Role::Admin->value]);
        $user->setIsActive(true);
        $user->setPassword($this->hasher->hashPassword($user, self::PASSWORD));

        $this->em->persist($user);
        $this->em->flush();

        $io->success(sprintf('Admin created: %s / %s', self::EMAIL, self::PASSWORD));

        return Command::SUCCESS;
    }
}
 