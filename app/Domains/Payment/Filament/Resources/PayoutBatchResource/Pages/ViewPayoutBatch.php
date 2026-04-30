<?php

declare(strict_types=1);

namespace App\Domains\Payment\Filament\Resources\PayoutBatchResource\Pages;

use App\Domains\Payment\Filament\Resources\PayoutBatchResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPayoutBatch extends ViewRecord
{
    protected static string $resource = PayoutBatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
