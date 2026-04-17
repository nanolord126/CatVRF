<?php declare(strict_types=1);

namespace App\Domains\BigData\Jobs;

use App\Domains\BigData\Services\ClickHouseService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

final class AggregationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly string $source,
        private readonly string $type,
        private readonly string $key,
        private readonly float $value,
    ) {}

    public function onQueue(): string
    {
        return 'bigdata';
    }

    public function handle(ClickHouseService $service): void
    {
        $service->aggregate($this->source, $this->type, $this->key, $this->value);
        Log::channel('audit')->info('Big data aggregation job completed');
    }
}
