<?php

declare(strict_types=1);

namespace App\Domains\Payment\Filament\Resources\PayoutBatchResource\Pages;

use App\Domains\Payment\Filament\Resources\PayoutBatchResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPayoutBatches extends ListRecords
{
    protected static string $resource = PayoutBatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
