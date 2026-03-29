<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

final class FlushCacheCommand extends Command
{
    protected $signature = 'cache:flush-tags {--tag=} {--all}';
    protected $description = 'Flush cache by tags or completely';

    public function handle(): int
    {
        if ($this->option('all')) {
            Cache::flush();
            $this->info('All cache flushed successfully');
            return self::SUCCESS;
        }

        if ($tag = $this->option('tag')) {
            Cache::store('redis')->tags([$tag])->flush();
            $this->info("Cache flushed for tag: {$tag}");
            return self::SUCCESS;
        }

        $this->error('Specify --tag or --all option');
        return self::FAILURE;
    }
}
