<?php

declare(strict_types=1);

namespace App\Filament\Resources\CdnResource\Pages;

use App\Filament\Resources\CdnResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListCdns extends ListRecords
{
    protected static string $resource = CdnResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
