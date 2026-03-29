<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Flowers\Pages;

use use App\Filament\Tenant\Resources\FlowersResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditFlowers extends EditRecord
{
    protected static string $resource = FlowersResource::class;

    public function getTitle(): string
    {
        return 'Edit Flowers';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}