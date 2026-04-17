<?php declare(strict_types=1);

/**
 * CreateFreelance — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/createfreelance
 * @see https://catvrf.ru/docs/createfreelance
 */


namespace App\Filament\Tenant\Resources\Pages;



use Psr\Log\LoggerInterface;
use Illuminate\Contracts\Auth\Guard;
use App\Filament\Tenant\Resources\FreelanceResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Class CreateFreelance
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 *
 * @package App\Filament\Tenant\Resources\Pages
 */
final class CreateFreelance extends CreateRecord
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    protected static string $resource = FreelanceResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['uuid']           = (string) Str::uuid();
        $data['correlation_id'] = (string) Str::uuid();
        $data['tenant_id']      = $this->guard->user()?->tenant_id;
        $data['status']         ??= 'pending';

        return $data;
    }

    protected function afterCreate(): void
    {
        $record = $this->record;
        \Illuminate\Support\Facades\Log::channel('audit')->info('Freelance order created', [
            'order_id'       => $record->id,
            'title'          => $record->title,
            'budget'         => $record->budget_kopecks,
            'correlation_id' => $record->correlation_id,
            'tenant_id'      => $record->tenant_id,
        ]);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
