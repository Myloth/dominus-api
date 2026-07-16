<?php

namespace App\Enum;

enum RoleEnum: string
{
    case ROLE_SUPERADMIN = 'ROLE_SUPERADMIN';
    case ROLE_ADMIN = 'ROLE_ADMIN';
    case ROLE_MODERATOR = 'ROLE_MODERATOR';
}
