<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Confectionery\Pages;

use use App\Filament\Tenant\Resources\ConfectioneryResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditConfectionery extends EditRecord
{
    protected static string $resource = ConfectioneryResource::class;

    public function getTitle(): string
    {
        return 'Edit Confectionery';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}