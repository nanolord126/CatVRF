<?php

declare(strict_types=1);

namespace App\Domains\Payment\Filament\Resources\PayoutBatchResource\Pages;

use App\Domains\Payment\Filament\Resources\PayoutBatchResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPayoutBatch extends EditRecord
{
    protected static string $resource = PayoutBatchResource::class;

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
