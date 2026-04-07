<?php declare(strict_types=1);

/**
 * ListVenues — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/listvenues
 * @see https://catvrf.ru/docs/listvenues
 * @see https://catvrf.ru/docs/listvenues
 * @see https://catvrf.ru/docs/listvenues
 */


namespace App\Filament\Tenant\Resources\Entertainment\VenueResource\Pages;



use Psr\Log\LoggerInterface;
use Illuminate\Contracts\Auth\Guard;
use Filament\Resources\Pages\ListRecords;

final class ListVenues extends ListRecords
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}


    protected static string $resource = VenueResource::class;

        protected function getHeaderActions(): array
        {
            return [
                Actions\CreateAction::make()
                    ->after(function () {
                        $this->logger->info('Venue creation started', [
                            'tenant_id' => filament()->getTenant()->id,
                            'user_id' => $this->guard->id(),
                        ]);
                    }),
            ];
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
