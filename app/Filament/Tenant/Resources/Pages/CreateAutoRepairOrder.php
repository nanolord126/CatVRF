<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Pages;




use Illuminate\Http\Request;
use Psr\Log\LoggerInterface;
use Illuminate\Contracts\Auth\Guard;
use App\Filament\Tenant\Resources\AutoRepairOrderResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Class CreateAutoRepairOrder
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 *
 * @package App\Filament\Tenant\Resources\Pages
 */
final class CreateAutoRepairOrder extends CreateRecord
{
    public function __construct(
        private readonly Request $request,
        private readonly LoggerInterface $logger,
    ) {}

    protected static string $resource = AutoRepairOrderResource::class;

    public function getTitle(): string
    {
        return 'Создание заказ-наряда';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['uuid'] = $data['uuid'] ?? (string) Str::uuid();
        $data['correlation_id'] = $data['correlation_id'] ?? (string) ($this->request->header('X-Correlation-ID') ?? Str::uuid());
        $data['tenant_id'] = $data['tenant_id'] ?? $this->guard->user()?->tenant_id;
        $data['business_group_id'] = $data['business_group_id'] ?? $this->guard->user()?->business_group_id;
        $data['status'] = $data['status'] ?? 'pending';
        $data['labor_cost_kopecks'] = $data['labor_cost_kopecks'] ?? 0;
        $data['parts_cost_kopecks'] = $data['parts_cost_kopecks'] ?? 0;
        $data['total_cost_kopecks'] = $data['total_cost_kopecks'] ?? 0;

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->logger->info('Auto repair order created', [
            'order_id' => $this->record->id ?? null,
            'tenant_id' => $this->record->tenant_id ?? null,
            'correlation_id' => $this->record->correlation_id ?? null,
        ]);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
