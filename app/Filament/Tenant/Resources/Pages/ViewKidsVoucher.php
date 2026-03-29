<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\KidsVoucher\Pages;

use use App\Filament\Tenant\Resources\KidsVoucherResource;;
use Filament\Resources\Pages\ViewRecord;

final class ViewKidsVoucher extends ViewRecord
{
    protected static string $resource = KidsVoucherResource::class;

    public function getTitle(): string
    {
        return 'View KidsVoucher';
    }
}