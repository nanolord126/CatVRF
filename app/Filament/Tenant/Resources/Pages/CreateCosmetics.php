<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Cosmetics\Pages;

use use App\Filament\Tenant\Resources\CosmeticsResource;;
use Filament\Resources\Pages\CreateRecord;

final class CreateCosmetics extends CreateRecord
{
    protected static string $resource = CosmeticsResource::class;

    public function getTitle(): string
    {
        return 'Create Cosmetics';
    }
}