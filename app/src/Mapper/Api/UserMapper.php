<?php

namespace App\Mapper\Api;

use App\Dto\Api\Common\PaginationDto;
use App\Dto\Api\Common\PaginationResponseDto;
use App\Dto\Api\User\UpdateUserDto;
use App\Dto\Api\User\UserResponseDto;
use App\Dto\Api\User\UsersResponseDto;
use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserMapper
{
    public function toResponseDto(User $user): UserResponseDto
    {
        return new UserResponseDto(
            id: $user->getId(),
            email: $user->getEmail(),
            firstname: $user->getFirstname(),
            lastname: $user->getLastname(),
            roles: array_map(
                fn (string $role) => strtolower(str_replace('ROLE_', '', $role)),
                $user->getRoles(),
            ),
        );
    }

    public function toListResponseDto(
        array $users,
        int $total,
        PaginationDto $paginationDto,
    ): UsersResponseDto {
        $data = array_map(
            $this->toResponseDto(...),
            $users,
        );

        return new UsersResponseDto(
            data: $data,
            meta: new PaginationResponseDto(
                total: $total,
                page: $paginationDto->page,
                limit: $paginationDto->limit,
                totalPages: (int) ceil(
                    $total / $paginationDto->limit,
                ),
            ),
        );
    }

    public function applyUpdate(UpdateUserDto $updateUser, User $user, UserPasswordHasherInterface $passwordHasher)
    {
        if (isset($updateUser->firstname)) {
            $user->setFirstname($updateUser->firstname);
        }
        if (isset($updateUser->lastname)) {
            $user->setLastname($updateUser->lastname);
        }
        if (isset($updateUser->email)) {
            $user->setEmail($updateUser->email);
        }
        if (isset($updateUser->plainPassword)) {
            $user->setPassword($passwordHasher->hashPassword($user, $updateUser->plainPassword));
        }
        if (isset($updateUser->roles)) {
            $user->setRoles(array_map(
                fn (string $role) => 'ROLE_'.strtoupper($role),
                $updateUser->roles,
            ));
        }
        if (isset($updateUser->active)) {
            $user->setIsActive($updateUser->active);
        }
    }
}
