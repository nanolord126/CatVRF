<?php

declare(strict_types=1);

namespace App\Domains\Payment\Filament\Resources\PaymentIntentResource\Pages;

use App\Domains\Payment\Filament\Resources\PaymentIntentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPaymentIntent extends EditRecord
{
    protected static string $resource = PaymentIntentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
