<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\LuxuryProduct\Pages;

use use App\Filament\Tenant\Resources\LuxuryProductResource;;
use Filament\Resources\Pages\CreateRecord;

final class CreateLuxuryProduct extends CreateRecord
{
    protected static string $resource = LuxuryProductResource::class;

    public function getTitle(): string
    {
        return 'Create LuxuryProduct';
    }
}