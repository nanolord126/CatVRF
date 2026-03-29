<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\CacheWarmers\WarmUserTasteProfileJob;
use App\Jobs\CacheWarmers\WarmPopularProductsJob;
use App\Jobs\CacheWarmers\WarmVerticalStatsJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

final class WarmCacheCommand extends Command
{
    protected $signature = 'cache:warm {--vertical=} {--user-id=}';
    protected $description = 'Warm up cache for improved performance';

    public function handle(): int
    {
        if ($userId = $this->option('user-id')) {
            dispatch(new WarmUserTasteProfileJob((int)$userId));
            $this->info("Queued cache warming for user {$userId}");
        }

        if ($vertical = $this->option('vertical')) {
            dispatch(new WarmPopularProductsJob($vertical));
            dispatch(new WarmVerticalStatsJob($vertical));
            $this->info("Queued cache warming for vertical: {$vertical}");
        }

        if (!$userId && !$vertical) {
            // Warm all verticals
            $verticals = DB::table('verticals')->distinct()->pluck('code');

            foreach ($verticals as $v) {
                dispatch(new WarmPopularProductsJob($v));
                dispatch(new WarmVerticalStatsJob($v));
            }

            $this->info("Queued cache warming for " . count($verticals) . " verticals");
        }

        return self::SUCCESS;
    }
}
