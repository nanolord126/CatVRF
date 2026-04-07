<?php

declare(strict_types=1);

namespace App\Domains\Payment\Filament\Resources\PaymentRecordResource\Pages;

use App\Domains\Payment\Filament\Resources\PaymentRecordResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

/**
 * Страница редактирования платёжной записи в Filament.
 */
final class EditPaymentRecord extends EditRecord
{
    protected static string $resource = PaymentRecordResource::class;

    /**
     * Действия заголовка.
     *
     * @return array<Actions\Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
