<?php

namespace App\Filament\Tenant\Resources\Pages;

use App\Filament\Tenant\Resources\BeautyProductResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBeautyProduct extends EditRecord
{
    protected static string $resource = BeautyProductResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
