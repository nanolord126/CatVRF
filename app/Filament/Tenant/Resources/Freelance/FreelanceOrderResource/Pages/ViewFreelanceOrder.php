<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Freelance\FreelanceOrderResource\Pages;

use App\Filament\Tenant\Resources\Freelance\FreelanceOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

final class ViewFreelanceOrder extends ViewRecord
{
    protected static string $resource = FreelanceOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
