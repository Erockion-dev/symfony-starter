<?php

namespace App\Enum;

enum Role: string
{
    case User = 'ROLE_USER';
    case Admin = 'ROLE_ADMIN';
    case Operator = 'ROLE_OPERATOR';
    
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
