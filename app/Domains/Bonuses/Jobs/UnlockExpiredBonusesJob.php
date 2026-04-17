<?php declare(strict_types=1);

namespace App\Domains\Bonuses\Jobs;

use App\Domains\Bonuses\Services\BonusService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

final class UnlockExpiredBonusesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly BonusService $bonusService,
    ) {}

    public function onQueue(): string
    {
        return 'default';
    }

    public function handle(): void
    {
        $unlocked = $this->bonusService->unlockExpiredHolds();

        Log::channel('audit')->info('Expired bonus holds unlocked', [
            'unlocked_count' => $unlocked,
        ]);
    }
}
