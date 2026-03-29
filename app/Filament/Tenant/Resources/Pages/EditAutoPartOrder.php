<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\AutoPartOrder\Pages;

use use App\Filament\Tenant\Resources\AutoPartOrderResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditAutoPartOrder extends EditRecord
{
    protected static string $resource = AutoPartOrderResource::class;

    public function getTitle(): string
    {
        return 'Edit AutoPartOrder';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}