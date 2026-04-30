<?php

declare(strict_types=1);

namespace App\Filament\Resources\WarehouseBatchResource\Pages;

use App\Filament\Resources\WarehouseBatchResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\CreateRecord;

final class CreateWarehouseBatch extends CreateRecord
{
    protected static string $resource = WarehouseBatchResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
