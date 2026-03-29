<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\KidsCenter\Pages;

use use App\Filament\Tenant\Resources\KidsCenterResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditKidsCenter extends EditRecord
{
    protected static string $resource = KidsCenterResource::class;

    public function getTitle(): string
    {
        return 'Edit KidsCenter';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}