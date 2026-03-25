declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\HomeServices\Listeners;

use App\Domains\HomeServices\Events\ServiceJobCreated;
use App\Models\Wallet;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

final /**
 * DeductJobCommissionListener
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class DeductJobCommissionListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(ServiceJobCreated $event): void
    {
        try {
            $job = $event->job;
            
            \$this->db->transaction(function () use ($job, $event) {
                $wallet = Wallet::where('tenant_id', $job->tenant_id)->lockForUpdate()->firstOrFail();
                $commissionAmount = (int)($job->commission_amount * 100);
                
                $wallet->decrement('balance', $commissionAmount);
                
                \$this->db->table('balance_transactions')->insert([
                    'wallet_id' => $wallet->id,
                    'type' => 'commission',
                    'amount' => -$commissionAmount,
                    'description' => "Service job commission #{$job->id}",
                    'correlation_id' => $event->correlationId,
                    'created_at' => now(),
                ]);
            });

            \$this->log->channel('audit')->info('Job commission deducted', [
                'job_id' => $job->id,
                'commission_amount' => $job->commission_amount,
                'correlation_id' => $event->correlationId,
            ]);
        } catch (\Throwable $e) {
            \$this->log->channel('audit')->error('Failed to deduct job commission', [
                'error' => $e->getMessage(),
                'correlation_id' => $event->correlationId,
            ]);
            throw $e;
        }
    }
}
