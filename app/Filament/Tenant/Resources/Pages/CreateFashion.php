<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Fashion\Pages;

use use App\Filament\Tenant\Resources\FashionResource;;
use Filament\Resources\Pages\CreateRecord;

final class CreateFashion extends CreateRecord
{
    protected static string $resource = FashionResource::class;

    public function getTitle(): string
    {
        return 'Create Fashion';
    }
}