<?php

namespace App\Controller\Api\Admin;

use App\Dto\Api\Common\ErrorResponseDto;
use App\Dto\Api\Common\PaginationDto;
use App\Dto\Api\User\CreateUserDto;
use App\Dto\Api\User\UpdateUserDto;
use App\Entity\User;
use App\Mapper\Api\UserMapper;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/api/admin/users', name: 'app_api_admin_users_')]
final class UserController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        private UserMapper $userMapper,
        private UserPasswordHasherInterface $passwordHasher,
    ) {}

    #[Route(name: 'index', methods: ['GET'])]
    public function index(
        #[MapQueryString()] PaginationDto $paginationDto,
        
    ): JsonResponse
    {
        $result = $this->userRepository->findAllPaginated(
            $paginationDto->page,
            $paginationDto->limit,
        );

        $response = $this->userMapper->toListResponseDto($result['users'], $result['total'], $paginationDto);
        
        return $this->json($response, Response::HTTP_OK);
    }

    #[Route(
        '/{id}',
        name: 'show',
        requirements: ['id' => '\d+'],
        methods: ['GET'],
    )]
    public function show(int $id): JsonResponse
    {
        $user = $this->userRepository->find($id);

        if (!$user) {
            return $this->json(new ErrorResponseDto(
                message: 'User with id '.$id. ' is not found.',
                error: 'Not Found',
                statusCode: 404,
            ), 400);
        }

        $response = $this->userMapper->toResponseDto($user);

        return $this->json($response, Response::HTTP_OK);
    }

    #[Route(name: 'new', methods: ['POST'])]
    public function create(#[MapRequestPayload()] CreateUserDto $createUerDto): JsonResponse
    {

        $user = new User();
        $user->setEmail($createUerDto->email);
        $user->setFirstname($createUerDto->firstname);
        $user->setLastname($createUerDto->lastname);
        $user->setPassword(
            $this->passwordHasher->hashPassword($user, $createUerDto->password)
        );
        $roles = array_map(
            fn (string $role) => 'ROLE_'. strtoupper($role),
            $createUerDto->roles
        );
        $user->setRoles($roles);

        $this->entityManager->persist($user);

        try {
            $this->entityManager->flush();
        } catch (\Throwable $e) {
            return $this->json(new ErrorResponseDto(
                message: 'Error while creating the user.',
                error: 'Bad Request',
                statusCode: 400,
            ), 400);
        }
        
        return $this->json(
            $this->userMapper->toResponseDto($user),
            JsonResponse::HTTP_CREATED,
        );
    }

    #[Route('/{id}', name: 'edit', methods: ['PATCH'], requirements: ['id' => '\d+'])]
    public function edit(
        int $id,
        #[MapRequestPayload()]UpdateUserDto $updateUserDto
    ): JsonResponse
    {
        $user = $this->userRepository->find($id);

        if (!$user) {
            return $this->json(new ErrorResponseDto(
                message: 'User with id '.$id. ' is not found.',
                error: 'Not Found',
                statusCode: 404,
            ), 400);
        }

        $this->userMapper->applyUpdate($updateUserDto, $user, $this->passwordHasher);

        $this->entityManager->persist($user);

        try {
            $this->entityManager->flush();
        } catch (\Throwable $e) {
            return $this->json(new ErrorResponseDto(
                message: 'Error while creating the user.',
                error: 'Bad Request',
                statusCode: 400,
            ), 400);
        }

        $response = $this->userMapper->toResponseDto($user);
        
        return $this->json($response, JsonResponse::HTTP_OK);
    }

    #[Route('/{id}', name: 'soft_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function softDelete(int $id): JsonResponse
    {
        $user = $this->userRepository->find($id);

        if (!$user || $user->getDeletedAt() !== null) {
            return $this->json(new ErrorResponseDto(
                message: 'User with id '.$id. ' is not found.',
                error: 'Not Found',
                statusCode: 404,
            ), 404);
        }

        $user->setDeletedAt(new \DateTimeImmutable());
        $user->setIsActive(false);
        $this->entityManager->flush();

        return $this->json(null, JsonResponse::HTTP_NO_CONTENT);
    }
}
