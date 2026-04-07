<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Beauty\ServiceResource\Pages;

use App\Filament\Tenant\Resources\Beauty\ServiceResource;
use App\Services\FraudControlService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * Создание услуги. Filament Page.
 *
 * Сервисы резолвятся через app() — constructor injection не поддерживается Livewire 3.
 * Нет Facades. correlation_id + FraudControlService::check() + DB::transaction().
 *
 * @package App\Filament\Tenant\Resources\Beauty\ServiceResource\Pages
 */
final class CreateService extends CreateRecord
{
    protected static string $resource = ServiceResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $db = app(DatabaseManager::class);
        $logger = app(LoggerInterface::class);

        return $db->transaction(function () use ($data, $logger): Model {
            $correlationId = (string) Str::uuid();
            $tenantId = filament()->getTenant()?->id;

            app(FraudControlService::class)->check(
                userId: filament()->auth()->id() ?? 0,
                operationType: 'create-service',
                amount: $data['price'] ?? 0,
                correlationId: $correlationId,
            );

            $logger->info('Service created', [
                'data'           => $data,
                'tenant_id'      => $tenantId,
                'correlation_id' => $correlationId,
            ]);

            $data['tenant_id']      = $tenantId;
            $data['correlation_id'] = $correlationId;

            return static::getModel()::create($data);
        });
    }
}
