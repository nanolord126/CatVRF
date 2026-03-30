<?php declare(strict_types=1);

namespace App\Jobs\Bonus;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class BonusUnlockJob extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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
                    Log::channel('audit')->info('Bonus unlock job completed', [
                        'correlation_id' => $this->correlationId,
                        'unlocked_count' => $unlockedCount,
                    ]);
                }
            } catch (\Exception $e) {
                Log::channel('audit')->error('Bonus unlock job failed', [
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
