<?php

declare(strict_types=1);

namespace App\Enums\AI;

enum ConstructorType: string
{
    case INTERIOR = 'interior';
    case BEAUTY_LOOK = 'beauty_look';
    case OUTFIT = 'outfit';
    case CAKE = 'cake';
    case MENU = 'menu';
}
