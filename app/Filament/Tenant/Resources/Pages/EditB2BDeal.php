<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\B2BDeal\Pages;

use use App\Filament\Tenant\Resources\B2BDealResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditB2BDeal extends EditRecord
{
    protected static string $resource = B2BDealResource::class;

    public function getTitle(): string
    {
        return 'Edit B2BDeal';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}