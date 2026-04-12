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
    public function __construct(private readonly \Illuminate\Database\DatabaseManager $db,
        private readonly Request $request, private readonly LoggerInterface $logger, private readonly Guard $guard) {}

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 120;

    public function handle(): void
    {
        try {
            $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');
            $this->db->transaction(function () {
                $studios = PhotoStudio::all();
                foreach ($studios as $studio) {
                    $avgRating = $studio->reviews()->avg('rating') ?? 0;
                    $reviewCount = $studio->reviews()->count();

                    $studio->update([
                        'rating' => $avgRating,
                        'review_count' => $reviewCount,
                    ]);
                }

                $photographers = Photographer::all();
                foreach ($photographers as $photographer) {
                    $avgRating = $photographer->reviews()->avg('rating') ?? 0;
                    $photographer->update(['rating' => $avgRating]);
                }

                $this->logger->info('Photography: Batch ratings calculated', [
                    'studios_count' => $studios->count(),
                    'photographers_count' => $photographers->count(),
                'correlation_id' => $this->request->header('X-Correlation-ID', $this->correlationId ?? ''),
            ]);
            });
        } catch (\Throwable $e) {
            $this->logger->error('Photography: Ratings calculation failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $this->request->header('X-Correlation-ID', $this->correlationId ?? ''),
            ]);
            throw $e;
        }
    }
}
