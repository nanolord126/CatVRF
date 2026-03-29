<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\LuxuryProduct\Pages;

use use App\Filament\Tenant\Resources\LuxuryProductResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditLuxuryProduct extends EditRecord
{
    protected static string $resource = LuxuryProductResource::class;

    public function getTitle(): string
    {
        return 'Edit LuxuryProduct';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}