<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\KidsVoucher\Pages;

use use App\Filament\Tenant\Resources\KidsVoucherResource;;
use Filament\Resources\Pages\CreateRecord;

final class CreateKidsVoucher extends CreateRecord
{
    protected static string $resource = KidsVoucherResource::class;

    public function getTitle(): string
    {
        return 'Create KidsVoucher';
    }
}