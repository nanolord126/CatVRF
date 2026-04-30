<?php

declare(strict_types=1);

namespace App\Filament\Resources\CooldownPeriodResource\Pages;

use App\Filament\Resources\CooldownPeriodResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

final class ViewCooldownPeriod extends ViewRecord
{
    protected static string $resource = CooldownPeriodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(false), // Disable edit - cooldowns should not be edited directly
        ];
    }
}
