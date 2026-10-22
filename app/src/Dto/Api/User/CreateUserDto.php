<?php

namespace App\Dto\Api\User;

use Symfony\Component\Validator\Constraints as Assert;

class CreateUserDto
{
    #[Assert\NotBlank]
    #[Assert\Email]
    #[Assert\Length(max: 180)]
    public string $email;

    #[Assert\NotBlank]
    #[Assert\Length(min: 1, max: 100)]
    public string $firstname;

    #[Assert\NotBlank]
    #[Assert\Length(min: 1, max: 100)]
    public string $lastname;

    #[Assert\NotBlank]
    #[Assert\Length(min: 8, max: 255)]
    public string $password;

    /**
     * @var string[]
     */
    #[Assert\NotBlank]
    #[Assert\Count(min: 1)]
    #[Assert\All([
        new Assert\Choice(choices: ['user', 'admin', 'operator']),
    ])]
    public array $roles = ['user'];
}
