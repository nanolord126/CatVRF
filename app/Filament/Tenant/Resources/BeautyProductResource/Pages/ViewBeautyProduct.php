<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\BeautyProductResource\Pages;

use App\Filament\Tenant\Resources\BeautyProductResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

final class ViewBeautyProduct extends ViewRecord
{
    protected static string $resource = BeautyProductResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\EditAction::make()];
    }
}
