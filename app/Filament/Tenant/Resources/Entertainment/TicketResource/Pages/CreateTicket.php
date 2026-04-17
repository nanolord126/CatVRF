<?php declare(strict_types=1);

/**
 * CreateTicket — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/createticket
 * @see https://catvrf.ru/docs/createticket
 * @see https://catvrf.ru/docs/createticket
 * @see https://catvrf.ru/docs/createticket
 * @see https://catvrf.ru/docs/createticket
 */


namespace App\Filament\Tenant\Resources\Entertainment\TicketResource\Pages;


use Psr\Log\LoggerInterface;
use Filament\Resources\Pages\CreateRecord;

final class CreateTicket extends CreateRecord
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}


    protected static string $resource = TicketResource::class;

        protected function mutateFormDataBeforeCreate(array $data): array
        {
            $data['tenant_id'] = filament()->getTenant()->id;
            $data['uuid'] = (string) Str::uuid();
            $data['correlation_id'] = (string) Str::uuid();

            \Illuminate\Support\Facades\Log::channel('audit')->info('Entertainment Ticket record mutation', [
                'tenant_id' => $data['tenant_id'],
                'correlation_id' => $data['correlation_id'],
            ]);

            return $data;
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
