<?php

declare(strict_types=1);

namespace App\Domains\Payment\Filament\Resources\PaymentRecordResource\Pages;

use App\Domains\Payment\Filament\Resources\PaymentRecordResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

/**
 * Страница создания платёжной записи в Filament.
 */
final class CreatePaymentRecord extends CreateRecord
{
    protected static string $resource = PaymentRecordResource::class;

    /**
     * Подставляем tenant_id, uuid, correlation_id перед сохранением.
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (function_exists('tenant') && tenant()) {
            $data['tenant_id'] = tenant()->id;
        }

        if (empty($data['uuid'])) {
            $data['uuid'] = Str::uuid()->toString();
        }

        if (empty($data['correlation_id'])) {
            $data['correlation_id'] = Str::uuid()->toString();
        }

        if (empty($data['status'])) {
            $data['status'] = 'pending';
        }

        return $data;
    }
}
