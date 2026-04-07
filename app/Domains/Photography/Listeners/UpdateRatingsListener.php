<?php declare(strict_types=1);

namespace App\Domains\Photography\Listeners;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
final class UpdateRatingsListener
{
    public function __construct(
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard) {}


    use InteractsWithQueue;
use App\Services\FraudControlService;

    	public function handle(ReviewSubmitted $event): void
    	{
    		try {
    			$this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');
    			$this->db->transaction(function () use ($event) {
    				$studio = PhotoStudio::find($event->review->photo_studio_id);
    				$photographer = Photographer::find($event->review->photographer_id);

    				if ($studio) {
    					$studio->update([
    						'rating' => $studio->reviews()->avg('rating') ?? 0,
    						'review_count' => $studio->reviews()->count(),
    					]);
    				}

    				if ($photographer) {
    					$photographer->update([
    						'rating' => $photographer->reviews()->avg('rating') ?? 0,
    					]);
    				}

    				$this->logger->info('Photography: Ratings updated', [
    					'studio_id' => $studio?->id,
    					'photographer_id' => $photographer?->id,
    					'correlation_id' => $event->correlationId,
    				]);
    			});
    		} catch (\Throwable $e) {
    			$this->logger->error('Photography: Rating update failed', [
    				'review_id' => $event->review->id,
    				'error' => $e->getMessage(),
    				'correlation_id' => $event->correlationId,
    			]);
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
