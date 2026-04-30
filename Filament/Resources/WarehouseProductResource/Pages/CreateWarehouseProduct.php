<?php

declare(strict_types=1);

namespace App\Filament\Resources\WarehouseProductResource\Pages;

use App\Filament\Resources\WarehouseProductResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\CreateRecord;

final class CreateWarehouseProduct extends CreateRecord
{
    protected static string $resource = WarehouseProductResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
