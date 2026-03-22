<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\BeautyResource\Pages;

use App\Filament\Tenant\Resources\BeautyResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

final class ViewBeautySalon extends ViewRecord
{
    protected static string $resource = BeautyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
