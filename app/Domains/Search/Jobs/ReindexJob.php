<?php declare(strict_types=1);

namespace App\Domains\Search\Jobs;

use App\Domains\Search\Services\SearchService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

final class ReindexJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly string $type,
    ) {}

    public function onQueue(): string
    {
        return 'search';
    }

    public function handle(SearchService $service): void
    {
        $service->rebuild($this->type);
        Log::channel('audit')->info('Search reindex job completed', [
            'type' => $this->type,
        ]);
    }
}
