<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\VeganProduct\Pages;

use use App\Filament\Tenant\Resources\VeganProductResource;;
use Filament\Resources\Pages\CreateRecord;

final class CreateVeganProduct extends CreateRecord
{
    protected static string $resource = VeganProductResource::class;

    public function getTitle(): string
    {
        return 'Create VeganProduct';
    }
}