<?php

declare(strict_types=1);

namespace App\Domains\Payment\Filament\Resources\PayoutBatchResource\Pages;

use App\Domains\Payment\Filament\Resources\PayoutBatchResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePayoutBatch extends CreateRecord
{
    protected static string $resource = PayoutBatchResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
