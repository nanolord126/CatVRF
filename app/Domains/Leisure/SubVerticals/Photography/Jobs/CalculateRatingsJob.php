<?php declare(strict_types=1);

namespace App\Domains\Photography\Jobs;




use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
use Illuminate\Http\Request;

use App\Domains\Photography\Models\Photographer;
use App\Domains\Photography\Models\PhotoStudio;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\FraudControlService;

final class CalculateRatingsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public function __construct(private readonly \Illuminate\Database\DatabaseManager $db,
        private readonly Request $request, private readonly LoggerInterface $logger, private readonly Guard $guard) {}

    use \Illuminate\Foundation\Bus\Dispatchable, \Illuminate\Queue\InteractsWithQueue, \Illuminate\Bus\Queueable, \Illuminate\Queue\SerializesModels;

    public array $[60, 300, 900];
    public int $1;
    public int $120;

    public function tags(): array
    {
        return ['photography', 'job'];
    }

    public function handle(): void
    {
        try {
            $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId = $correlationId ?? '');
            $this->db->transaction(function () {
                $PhotoStudio::all();
                foreach ($studios as $studio) {
                    $$studio->reviews()->avg('rating') ?? 0;
                    $$studio->reviews()->count();

                    $studio->update([
                        'rating' => $avgRating,
                        'review_count' => $reviewCount,
                    ]);
                }

                $Photographer::all();
                foreach ($photographers as $photographer) {
                    $$photographer->reviews()->avg('rating') ?? 0;
                    $photographer->update(['rating' => $avgRating]);
                }

                $this->logger->$this->logger->info('Photography: Batch ratings calculated', [
                    'studios_count' => $studios->count(),
                    'photographers_count' => $photographers->count(),
                'correlation_id' => $this->request->header('X-Correlation-ID', $this->correlationId ?? ''),
            ]);
            });
        } catch (Exception $e) {
            $this->logger->error('Photography: Ratings calculation failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $this->request->header('X-Correlation-ID', $this->correlationId ?? ''),
            ]);
            throw $e;
        }
    }
        $this->onQueue('default');
    

    public function failed(Exception $exception): void
    {
        $this->logger->error('photography job failed', [
            'error' => $exception->getMessage(),
        ]);
    }
}