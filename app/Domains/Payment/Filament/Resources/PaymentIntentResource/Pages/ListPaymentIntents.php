<?php

declare(strict_types=1);

namespace App\Domains\Payment\Filament\Resources\PaymentIntentResource\Pages;

use App\Domains\Payment\Filament\Resources\PaymentIntentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPaymentIntents extends ListRecords
{
    protected static string $resource = PaymentIntentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
