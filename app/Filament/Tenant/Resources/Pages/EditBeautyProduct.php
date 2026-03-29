<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\BeautyProduct\Pages;

use use App\Filament\Tenant\Resources\BeautyProductResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditBeautyProduct extends EditRecord
{
    protected static string $resource = BeautyProductResource::class;

    public function getTitle(): string
    {
        return 'Edit BeautyProduct';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}