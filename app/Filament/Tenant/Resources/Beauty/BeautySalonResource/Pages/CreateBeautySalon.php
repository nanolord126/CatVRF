<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Beauty\BeautySalonResource\Pages;

use App\Filament\Tenant\Resources\Beauty\BeautySalonResource;
use App\Services\FraudControlService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * CreateBeautySalon — создание салона красоты.
 *
 * Filament Tenant Panel page.
 * Fraud-check + DB::transaction + correlation_id + audit log.
 *
 * CANON 2026: сервисы резолвятся через app(), no facades.
 *
 * @package CatVRF\Filament\Tenant
 * @version 2026.1
 */
final class CreateBeautySalon extends CreateRecord
{
    protected static string $resource = BeautySalonResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        /** @var \Illuminate\Database\DatabaseManager $db */
        $db = app(\Illuminate\Database\DatabaseManager::class);

        /** @var FraudControlService $fraud */
        $fraud = app(FraudControlService::class);

        /** @var LoggerInterface $logger */
        $logger = app(LoggerInterface::class);

        $correlationId = Str::uuid()->toString();

        return $db->transaction(static function () use ($data, $fraud, $logger, $correlationId): Model {
            $fraud->check(
                userId: (int) (filament()->auth()->id() ?? 0),
                operationType: 'create_beauty_salon',
                amount: 0,
                correlationId: $correlationId,
            );

            $data['tenant_id'] = filament()->getTenant()?->id;
            $data['correlation_id'] = $correlationId;

            $record = static::getModel()::create($data);

            $logger->info('BeautySalon created via Filament', [
                'salon_id'       => $record->id,
                'tenant_id'      => $data['tenant_id'],
                'correlation_id' => $correlationId,
            ]);

            return $record;
        });
    }
}
