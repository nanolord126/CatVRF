<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\NftGift\Pages;

use use App\Filament\Tenant\Resources\NftGiftResource;;
use Filament\Resources\Pages\CreateRecord;

final class CreateNftGift extends CreateRecord
{
    protected static string $resource = NftGiftResource::class;

    public function getTitle(): string
    {
        return 'Create NftGift';
    }
}