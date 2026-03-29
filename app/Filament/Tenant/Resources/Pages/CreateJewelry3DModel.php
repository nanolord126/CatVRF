<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Jewelry3DModel\Pages;

use use App\Filament\Tenant\Resources\Jewelry3DModelResource;;
use Filament\Resources\Pages\CreateRecord;

final class CreateJewelry3DModel extends CreateRecord
{
    protected static string $resource = Jewelry3DModelResource::class;

    public function getTitle(): string
    {
        return 'Create Jewelry3DModel';
    }
}