<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\KidsVoucher\Pages;

use use App\Filament\Tenant\Resources\KidsVoucherResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditKidsVoucher extends EditRecord
{
    protected static string $resource = KidsVoucherResource::class;

    public function getTitle(): string
    {
        return 'Edit KidsVoucher';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}