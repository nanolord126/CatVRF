<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Service\Pages;

use use App\Filament\Tenant\Resources\ServiceResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditService extends EditRecord
{
    protected static string $resource = ServiceResource::class;

    public function getTitle(): string
    {
        return 'Edit Service';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}