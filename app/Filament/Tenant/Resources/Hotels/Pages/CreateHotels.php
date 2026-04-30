<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Hotels\Pages;

use LoggerInterface;

use Carbon\CarbonImmutable;

use App\Filament\Tenant\Resources\Hotels\HotelsResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * CreateHotels — альтернативная страница создания записи отеля.
 *
 * Используется ресурсом HotelsResource для маршрута /create.
 * Tenant-scoped, correlation_id tracing, audit logging.
 */
final class CreateHotels extends CreateRecord
{
    protected static string $resource = HotelsResource::class;

    /**
     * Мутация данных формы перед созданием записи.
     *
     * Добавляет correlation_id, tenant_id и uuid для обеспечения
     * полной трассировки и tenant-scoping в multi-tenant среде.
     *
     * @param  array<string, mixed>  $data  Данные формы
     * @return array<string, mixed> Обогащённые данные
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['correlation_id'] = Str::uuid()->toString();
        $data['tenant_id'] = filament()->getTenant()?->id;
        $data['uuid'] = Str::uuid()->toString();

        return $data;
    }

    /**
     * Действия после успешного создания записи.
     *
     * Логирует факт создания с correlation_id для аудита.
     */
    protected function afterCreate(): void
    {
        $this->loggerInterface /* TODO: inject via constructor DI */ /* TODO: inject via DI */->$this->logger->info('Hotels record created', [
            'record_id' => $this->record->id,
            'uuid' => $this->record->uuid ?? null,
            'correlation_id' => $this->record->correlation_id ?? null,
            'user_id' => filament()->auth()->id(),
            'tenant_id' => filament()->getTenant()?->id,
            'timestamp' => CarbonImmutable::now()->toIso8601String(),
        ]);
    }

    /**
     * URL для перенаправления после создания.
     *
     * @return string URL списка записей
     */
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
