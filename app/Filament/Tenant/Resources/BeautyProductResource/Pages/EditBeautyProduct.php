<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\BeautyProductResource\Pages;

use App\Filament\Tenant\Resources\BeautyProductResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

final class EditBeautyProduct extends EditRecord
{
    protected static string $resource = BeautyProductResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\ViewAction::make(), Actions\DeleteAction::make()];
    }
}
