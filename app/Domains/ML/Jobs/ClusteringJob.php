<?php declare(strict_types=1);

namespace App\Domains\ML\Jobs;

use App\Domains\ML\Services\ClusteringService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

final class ClusteringJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly ClusteringService $clusteringService,
    ) {}

    public function onQueue(): string
    {
        return 'ml';
    }

    public function handle(): void
    {
        $this->clusteringService->runClustering();
        Log::channel('audit')->info('ML clustering job completed');
    }
}
