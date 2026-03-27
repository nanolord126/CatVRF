<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\PhotographerResource\Pages;

use App\Filament\Tenant\Resources\PhotographerResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePhotographer extends CreateRecord
{
    protected static string $resource = PhotographerResource::class;
}
