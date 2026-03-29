<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\WellnessCenter\Pages;

use use App\Filament\Tenant\Resources\WellnessCenterResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditWellnessCenter extends EditRecord
{
    protected static string $resource = WellnessCenterResource::class;

    public function getTitle(): string
    {
        return 'Edit WellnessCenter';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}