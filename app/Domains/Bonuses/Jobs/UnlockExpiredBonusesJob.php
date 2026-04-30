<?php

declare(strict_types=1);

namespace App\Domains\Bonuses\Jobs;

use Psr\Log\LoggerInterface;

use Illuminate\Support\Str;

use App\Domains\Bonuses\Services\BonusService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Log\LogManager;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class UnlockExpiredBonusesJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(private readonly LoggerInterface $logger,
        private readonly BonusService $bonusService,
        private readonly LogManager $log,
    ,
        public readonly string $correlationId = '') {}

    public function onQueue(): string
    {
        return 'default';
    }

    public function handle(): void
    {
        $correlationId = $this->correlationId ?: (string) Str::uuid();
        $unlocked = $this->bonusService->unlockExpiredHolds();

        $this->log->channel('audit')->$this->logger->info('Expired bonus holds unlocked', [
            'unlocked_count' => $unlocked,
        ]);
    }
}
