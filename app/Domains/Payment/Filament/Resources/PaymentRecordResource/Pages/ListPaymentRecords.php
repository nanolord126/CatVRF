<?php

declare(strict_types=1);

namespace App\Domains\Payment\Filament\Resources\PaymentRecordResource\Pages;

use App\Domains\Payment\Filament\Resources\PaymentRecordResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

/**
 * Страница списка платёжных записей в Filament.
 */
final class ListPaymentRecords extends ListRecords
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
            Actions\CreateAction::make(),
        ];
    }
}
