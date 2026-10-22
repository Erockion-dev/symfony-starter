<?php

namespace App\Dto\Api\User;

use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

class UpdateUserDto
{
    #[Assert\Length(min: 1, max: 100)]
    public ?string $firstname;

    #[Assert\Length(min: 1, max: 100)]
    public ?string $lastname;

    #[Assert\Email]
    #[Assert\Length(max: 180)]
    public ?string $email;

    #[SerializedName('password')]
    #[Assert\Length(min: 8, max: 255)]
    public ?string $plainPassword;

    /** @var list<string>|null */
    #[Assert\Count(min: 1)]
    #[Assert\All([new Assert\Choice(choices: ['admin', 'user', 'operator'])])]
    public ?array $roles;

    public ?bool $active;
}
