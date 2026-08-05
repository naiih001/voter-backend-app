<?php

namespace App\Enums;

enum Role: string
{
    case VOTER = 'voter';
    case ADMIN = 'admin';
}
