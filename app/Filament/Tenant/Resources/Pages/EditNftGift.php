<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\NftGift\Pages;

use use App\Filament\Tenant\Resources\NftGiftResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditNftGift extends EditRecord
{
    protected static string $resource = NftGiftResource::class;

    public function getTitle(): string
    {
        return 'Edit NftGift';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}