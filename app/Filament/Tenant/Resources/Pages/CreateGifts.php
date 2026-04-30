<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Pages;

use Psr\Log\LoggerInterface;

use Illuminate\Http\Request;
use App\Filament\Tenant\Resources\GiftsResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Log\LogManager;
use Illuminate\Support\Str;

/**
 * Class CreateGifts
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 */
final class CreateGifts extends CreateRecord
{
    protected static string $resource = GiftsResource::class;

    public function __construct(private readonly LoggerInterface $logger,
        private readonly Request $request,
        private readonly LogManager $log,) {}

    public function getTitle(): string
    {
        return 'Создание подарка';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['uuid'] = $data['uuid'] ?? (string) Str::uuid();
        $data['correlation_id'] = $data['correlation_id'] ?? (string) ($this->request->header('X-Correlation-ID') ?? Str::uuid());
        $data['tenant_id'] = $data['tenant_id'] ?? $this->guard->user()?->tenant_id;
        $data['business_group_id'] = $data['business_group_id'] ?? $this->guard->user()?->business_group_id;

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->log->channel('audit')->$this->logger->info('Gift product created', [
            'gift_id' => $this->record->id ?? null,
            'tenant_id' => $this->record->tenant_id ?? null,
            'correlation_id' => $this->record->correlation_id ?? null,
        ]);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
