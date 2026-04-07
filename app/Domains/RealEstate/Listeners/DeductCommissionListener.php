<?php declare(strict_types=1);

namespace App\Domains\RealEstate\Listeners;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
final class DeductCommissionListener
{
    public function __construct(
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard) {}


    public function handle(PropertySold $event): void
        {
            try {
                $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');
                $this->db->transaction(function () use ($event) {
                    $listing = $event->listing;
                    $commission = (int) ($listing->sale_price * $listing->commission_percent / 100);
                    // WalletService::debit($listing->property->owner_id, $commission, 'commission');

                    $this->logger->info('Commission deducted', [
                        'property_id' => $listing->property_id,
                        'commission' => $commission,
                        'correlation_id' => $event->correlationId,
                    ]);
                });
            } catch (\Throwable $e) {
                $this->logger->error('Failed to deduct commission', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $event->correlationId,
                ]);
                throw $e;
            }
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
