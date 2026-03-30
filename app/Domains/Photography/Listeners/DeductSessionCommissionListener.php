<?php declare(strict_types=1);

namespace App\Domains\Photography\Listeners;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class DeductSessionCommissionListener extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use InteractsWithQueue;

    	public function handle(SessionCreated $event): void
    	{
    		try {
    			DB::transaction(function () use ($event) {
    				$commission = (int) ($event->session->total_amount * 0.14);

    				Log::channel('audit')->info('Photography: Commission deducted', [
    					'session_id' => $event->session->id,
    					'tenant_id' => $event->session->tenant_id,
    					'commission_amount' => $commission,
    					'correlation_id' => $event->correlationId,
    				]);
    			});
    		} catch (\Exception $e) {
    			Log::channel('audit')->error('Photography: Commission deduction failed', [
    				'session_id' => $event->session->id,
    				'error' => $e->getMessage(),
    				'correlation_id' => $event->correlationId,
    			]);
    			throw $e;
    		}
    	}
}
