<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\B2BDealResource\Pages;

use App\Filament\Tenant\Resources\B2BDealResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

final class EditB2BDeal extends EditRecord
{
    protected static string $resource = B2BDealResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
