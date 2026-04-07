<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Hotels\HotelResource\Pages;

use App\Filament\Tenant\Resources\Hotels\HotelResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * CreateHotel — страница создания отеля для HotelResource.
 *
 * Filament v3 Page: tenant-scoped, correlation_id tracing, audit logging.
 *
 * @package App\Filament\Tenant\Resources\Hotels\HotelResource\Pages
 */
final class CreateHotel extends CreateRecord
{
    protected static string $resource = HotelResource::class;

    /**
     * Мутация данных формы перед созданием записи.
     *
     * @param array<string, mixed> $data Данные формы
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
     */
    protected function afterCreate(): void
    {
        app(LoggerInterface::class)->info('Hotel record created via HotelResource', [
            'record_id' => $this->record->id,
            'correlation_id' => $this->record->correlation_id ?? null,
            'user_id' => filament()->auth()->id(),
            'tenant_id' => filament()->getTenant()?->id,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * URL перенаправления после создания.
     *
     * @return string URL списка
     */
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
