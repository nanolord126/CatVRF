<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Beauty\ServiceResource\Pages;

use App\Filament\Tenant\Resources\Beauty\ServiceResource;
use App\Services\FraudControlService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * Редактирование услуги. Filament Page.
 *
 * Сервисы резолвятся через app() — constructor injection не поддерживается Livewire 3.
 * Нет Facades. correlation_id + FraudControlService::check() + DB::transaction().
 *
 * @package App\Filament\Tenant\Resources\Beauty\ServiceResource\Pages
 */
final class EditService extends EditRecord
{
    protected static string $resource = ServiceResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $db = app(DatabaseManager::class);
        $logger = app(LoggerInterface::class);

        return $db->transaction(function () use ($record, $data, $logger): Model {
            $correlationId = (string) Str::uuid();

            app(FraudControlService::class)->check(
                userId: filament()->auth()->id() ?? 0,
                operationType: 'edit-service',
                amount: $data['price'] ?? 0,
                correlationId: $correlationId,
            );

            $logger->info('Service updated', [
                'record_id'      => $record->id,
                'data'           => $data,
                'tenant_id'      => filament()->getTenant()?->id,
                'correlation_id' => $correlationId,
            ]);

            $record->update($data);

            return $record;
        });
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
