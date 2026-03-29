<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\HomeServices\Pages;

use use App\Filament\Tenant\Resources\HomeServicesResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditHomeServices extends EditRecord
{
    protected static string $resource = HomeServicesResource::class;

    public function getTitle(): string
    {
        return 'Edit HomeServices';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}