<?php

declare(strict_types=1);

namespace App\Domains\Payment\Filament\Resources\PaymentIntentResource\Pages;

use App\Domains\Payment\Filament\Resources\PaymentIntentResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePaymentIntent extends CreateRecord
{
    protected static string $resource = PaymentIntentResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
