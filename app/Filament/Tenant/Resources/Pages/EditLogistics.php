<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Logistics\Pages;

use use App\Filament\Tenant\Resources\LogisticsResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditLogistics extends EditRecord
{
    protected static string $resource = LogisticsResource::class;

    public function getTitle(): string
    {
        return 'Edit Logistics';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}