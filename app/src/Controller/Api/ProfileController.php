<?php

namespace App\Controller\Api;

use App\Mapper\Api\UserMapper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class ProfileController extends AbstractController
{
    #[Route('/api/users/profile', name: 'api_users_profile', methods: ['GET'])]
    public function show(UserMapper $userMapper): JsonResponse
    {
        return $this->json($userMapper->toResponseDto($this->getUser()));
    }
}
