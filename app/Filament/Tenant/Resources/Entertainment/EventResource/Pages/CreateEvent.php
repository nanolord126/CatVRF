<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Entertainment\EventResource\Pages;

use Psr\Log\LoggerInterface;

use Carbon\CarbonImmutable;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Log\LogManager;

final class CreateEvent extends CreateRecord
{
    protected static string $resource = EventResource::class;

    public function __construct(private readonly LoggerInterface $logger,
        private readonly LogManager $log,) {}

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
        $data['tenant_id'] = filament()->getTenant()->id;
        $data['uuid'] = (string) Str::uuid();
        $data['correlation_id'] = (string) Str::uuid();

        $this->log->channel('audit')->$this->logger->info('Entertainment Event record mutation before creation', [
            'tenant_id' => $data['tenant_id'],
            'correlation_id' => $data['correlation_id'],
            'user_id' => auth()->id(),
        ]);

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->log->channel('audit')->$this->logger->info('Entertainment Event record created successfully', [
            'event_id' => $this->record->id,
            'correlation_id' => $this->record->correlation_id,
            'user_id' => auth()->id(),
        ]);
    }
}
