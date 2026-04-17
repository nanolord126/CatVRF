<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Entertainment\EventResource\Pages;



use Psr\Log\LoggerInterface;
use Illuminate\Contracts\Auth\Guard;
use Filament\Resources\Pages\CreateRecord;

final class CreateEvent extends CreateRecord
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}


    protected static string $resource = EventResource::class;

        protected function mutateFormDataBeforeCreate(array $data): array
        {
            $data['tenant_id'] = filament()->getTenant()->id;
            $data['uuid'] = (string) Str::uuid();
            $data['correlation_id'] = (string) Str::uuid();

            \Illuminate\Support\Facades\Log::channel('audit')->info('Entertainment Event record mutation before creation', [
                'tenant_id' => $data['tenant_id'],
                'correlation_id' => $data['correlation_id'],
                'user_id' => auth()->id(),
            ]);

            return $data;
        }

        protected function afterCreate(): void
        {
            \Illuminate\Support\Facades\Log::channel('audit')->info('Entertainment Event record created successfully', [
                'event_id' => $this->record->id,
                'correlation_id' => $this->record->correlation_id,
                'user_id' => auth()->id(),
            ]);
        }

    /**
     * Get the string representation of this instance.
     *
     * @return string The string representation
     */
    public function __toString(): string
    {
        return static::class;
    }

    /**
     * Get debug information for this instance.
     *
     * @return array<string, mixed> Debug data including class name and state
     */
    public function toDebugArray(): array
    {
        return [
            'class' => static::class,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
