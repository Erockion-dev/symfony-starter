<?php

namespace App\Tests\Unit\Mapper;

use App\Dto\Api\Common\PaginationDto;
use App\Dto\Api\User\UpdateUserDto;
use App\Entity\User;
use App\Mapper\Api\UserMapper;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class UserMapperTest extends TestCase
{
    private UserMapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new UserMapper();
    }

    private function createUser(int $id = 1, string $email = 'user@example.com'): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setFirstname('User');
        $user->setLastname('One');
        $user->setRoles(['ROLE_USER']);
        $user->setIsActive(true);

        (new \ReflectionProperty(User::class, 'id'))->setValue($user, $id);

        return $user;
    }

    public function testToResponseDtoMapsFieldsAndFormatsRoles(): void
    {
        $user = $this->createUser(26);
        $user->setRoles(['ROLE_ADMIN']);

        $dto = $this->mapper->toResponseDto($user);

        $this->assertSame(26, $dto->id);
        $this->assertSame('user@example.com', $dto->email);
        $this->assertSame('User', $dto->firstname);
        $this->assertSame('One', $dto->lastname);
        $this->assertEqualsCanonicalizing(['admin', 'user'], $dto->roles);
    }

    public function testToListResponseDtoBuildsDataAndPagination(): void
    {
        $users = [
            $this->createUser(1, 'a@example.com'),
            $this->createUser(2, 'b@example.com'),
        ];

        $pagination = new PaginationDto();
        $pagination->page = 1;
        $pagination->limit = 10;

        $dto = $this->mapper->toListResponseDto($users, 25, $pagination);

        $this->assertCount(2, $dto->data);
        $this->assertSame('a@example.com', $dto->data[0]->email);
        $this->assertSame(25, $dto->meta->total);
        $this->assertSame(1, $dto->meta->page);
        $this->assertSame(10, $dto->meta->limit);
        $this->assertSame(3, $dto->meta->totalPages); // ceil(25 / 10)
    }

    public function testApplyUpdateOnlyChangesProvidedFields(): void
    {
        $hasher = $this->createMock(UserPasswordHasherInterface::class);
        $hasher->expects($this->never())->method('hashPassword');

        $user = $this->createUser();
        $dto = new UpdateUserDto();
        $dto->firstname = 'Jane';

        $this->mapper->applyUpdate($dto, $user, $hasher);

        $this->assertSame('Jane', $user->getFirstname());
        $this->assertSame('One', $user->getLastname());
        $this->assertSame('user@example.com', $user->getEmail());
        $this->assertTrue($user->isActive());
    }

    public function testApplyUpdateHashesThePassword(): void
    {
        $user = $this->createUser();

        $hasher = $this->createMock(UserPasswordHasherInterface::class);
        $hasher->expects($this->once())
            ->method('hashPassword')
            ->with($user, 'newpassword')
            ->willReturn('hashed');

        $dto = new UpdateUserDto();
        $dto->plainPassword = 'newpassword';

        $this->mapper->applyUpdate($dto, $user, $hasher);

        $this->assertSame('hashed', $user->getPassword());
    }

    public function testApplyUpdateConvertsRolesAndAppliesFalse(): void
    {
        $hasher = $this->createStub(UserPasswordHasherInterface::class);

        $user = $this->createUser();
        $dto = new UpdateUserDto();
        $dto->roles = ['operator'];
        $dto->active = false;

        $this->mapper->applyUpdate($dto, $user, $hasher);

        $this->assertEqualsCanonicalizing(['ROLE_OPERATOR', 'ROLE_USER'], $user->getRoles());
        $this->assertFalse($user->isActive());
    }
}
