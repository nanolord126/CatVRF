declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Jobs\Bonus;

use App\Services\Bonus\BonusService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final /**
 * BonusUnlockJob
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class BonusUnlockJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private string $correlationId;

    public function __construct()
    {
        $this->correlationId = Str::uuid()->toString();
        $this->onQueue('bonuses');
    }

    public function handle(BonusService $bonusService): void
    {
        try {
            $unlockedCount = $bonusService->unlockExpiredHolds();

            if ($unlockedCount > 0) {
                $this->log->channel('audit')->info('Bonus unlock job completed', [
                    'correlation_id' => $this->correlationId,
                    'unlocked_count' => $unlockedCount,
                ]);
            }
        } catch (\Exception $e) {
            $this->log->channel('audit')->error('Bonus unlock job failed', [
                'correlation_id' => $this->correlationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    public function tags(): array
    {
        return ['bonus', 'unlock', 'payout'];
    }
}
