<?php declare(strict_types=1);

namespace App\Console\Commands;



use Illuminate\Cache\CacheManager;
use Psr\Log\LoggerInterface;
use Illuminate\Cache\TaggableStore;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class FlushCacheCommand extends Command
{
    public function __construct(
        private readonly CacheManager $cache,
        private readonly LoggerInterface $logger,
    ) {}

    protected $signature = 'cache:flush-tags
        {--tag= : Cache tag to flush}
        {--all : Flush all cache stores}
        {--correlation-id= : Correlation identifier for audit logs}';

    protected $description = 'Flush cache by tag or entirely (Redis tags aware)';

    public function handle(): int
    {
        $correlationId = $this->option('correlation-id') ?: (string) Str::uuid();

        if ($this->option('all')) {
            $this->cache->flush();

            $this->logger->info('Cache fully flushed', [
                'scope' => 'all',
                'correlation_id' => $correlationId,
            ]);

            $this->info('All cache flushed successfully');
            return self::SUCCESS;
        }

        $tag = $this->option('tag');

        if ($tag !== null && $tag !== '') {
            /** @var TaggableStore|mixed $store */
            $store = $this->cache->store('redis');

            if ($store instanceof TaggableStore) {
                $store->tags([$tag])->flush();
            } else {
                $this->cache->tags([$tag])->flush();
            }

            $this->logger->info('Cache flushed by tag', [
                'tag' => $tag,
                'correlation_id' => $correlationId,
            ]);

            $this->info("Cache flushed for tag: {$tag}");
            return self::SUCCESS;
        }

        $this->error('Specify --tag or --all option');
        return self::FAILURE;
    }
}
