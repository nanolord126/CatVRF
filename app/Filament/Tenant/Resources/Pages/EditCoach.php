<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Coach\Pages;

use use App\Filament\Tenant\Resources\CoachResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditCoach extends EditRecord
{
    protected static string $resource = CoachResource::class;

    public function getTitle(): string
    {
        return 'Edit Coach';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}