<?php declare(strict_types=1);

namespace App\Domains\Photography\Listeners;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Bus\Dispatchable;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
final class DeductSessionCommissionListener
{
    public function __construct(
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard) {}



    	public function handle(SessionCreated $event): void
    	{
    		try {
    			$this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');
    			$this->db->transaction(function () use ($event) {
    				$commission = (int) ($event->session->total_amount * 0.14);

    				$this->logger->info('Photography: Commission deducted', [
    					'session_id' => $event->session->id,
    					'tenant_id' => $event->session->tenant_id,
    					'commission_amount' => $commission,
    					'correlation_id' => $event->correlationId,
    				]);
    			});
    		} catch (\Throwable $e) {
    			$this->logger->error('Photography: Commission deduction failed', [
    				'session_id' => $event->session->id,
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

