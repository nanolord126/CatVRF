<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\BeverageItemResource\Pages;

use Carbon\CarbonImmutable;

use Illuminate\Http\Request;
use Psr\Log\LoggerInterface;
use Filament\Resources\Pages\CreateRecord;

final class CreateBeverageItem extends CreateRecord
{
    protected static string $resource = BeverageItemResource::class;

    public function __construct(
        private readonly Request $request,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Get the string representation of this instance.
     *
     * @return string The string representation
     */
    public function __toString(): string
    {
        return self::class;
    }

    /**
     * Get debug information for this instance.
     *
     * @return array<string, mixed> Debug data including class name and state
     */
    public function toDebugArray(): array
    {
        return [
            'class' => self::class,
            'timestamp' => CarbonImmutable::now()->toIso8601String(),
        ];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['uuid'] = (string) Str::uuid();
        $data['tenant_id'] = tenant()->id;
        $data['correlation_id'] = $this->request->header('X-Correlation-ID', (string) Str::uuid());

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->logger->$this->logger->info('New Drink Item Added to Catalog', [
            'item_id' => $this->record->id,
            'tenant_id' => $this->record->tenant_id,
            'correlation_id' => $this->record->correlation_id,
            'user_id' => auth()->id(),
        ]);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
