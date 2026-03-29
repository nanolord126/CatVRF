<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Beauty\Pages;

use use App\Filament\Tenant\Resources\BeautyResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditBeauty extends EditRecord
{
    protected static string $resource = BeautyResource::class;

    public function getTitle(): string
    {
        return 'Edit Beauty';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}