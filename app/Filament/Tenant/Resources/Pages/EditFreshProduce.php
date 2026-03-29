<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\FreshProduce\Pages;

use use App\Filament\Tenant\Resources\FreshProduceResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditFreshProduce extends EditRecord
{
    protected static string $resource = FreshProduceResource::class;

    public function getTitle(): string
    {
        return 'Edit FreshProduce';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}