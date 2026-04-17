<?php declare(strict_types=1);

namespace App\Console\Commands;



use Illuminate\Database\DatabaseManager;
use Psr\Log\LoggerInterface;
use App\Jobs\CacheWarmers\WarmPopularProductsJob;
use App\Jobs\CacheWarmers\WarmUserTasteProfileJob;
use App\Jobs\CacheWarmers\WarmVerticalStatsJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class WarmCacheCommand extends Command
{
    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected $signature = 'cache:warm
        {--vertical= : Warm cache for a specific vertical}
        {--user-id= : Warm user-specific taste profile cache}
        {--correlation-id= : Correlation identifier for audit logs}';

    protected $description = 'Warm application cache for users or verticals to improve performance';

    public function handle(): int
    {
        $userId = $this->option('user-id');
        $vertical = $this->option('vertical');
        $correlationId = $this->option('correlation-id') ?: (string) Str::uuid();

        if ($userId) {
            WarmUserTasteProfileJob::dispatch((int) $userId);

            $this->logger->info('Cache warming queued for user taste profile', [
                'user_id' => (int) $userId,
                'correlation_id' => $correlationId,
            ]);

            $this->info("Queued cache warming for user {$userId}");
        }

        if ($vertical) {
            WarmPopularProductsJob::dispatch((string) $vertical);
            WarmVerticalStatsJob::dispatch((string) $vertical);

            $this->logger->info('Cache warming queued for vertical', [
                'vertical' => $vertical,
                'correlation_id' => $correlationId,
            ]);

            $this->info("Queued cache warming for vertical: {$vertical}");
        }

        if (!$userId && !$vertical) {
            $verticals = $this->db->table('verticals')->distinct()->pluck('code')->all();

            foreach ($verticals as $v) {
                WarmPopularProductsJob::dispatch($v);
                WarmVerticalStatsJob::dispatch($v);
            }

            $this->logger->info('Cache warming queued for all verticals', [
                'count' => count($verticals),
                'correlation_id' => $correlationId,
            ]);

            $this->info('Queued cache warming for ' . count($verticals) . ' verticals');
        }

        return Command::SUCCESS;
    }
}
