<?php

declare(strict_types=1);

namespace App\Domains\Photography\Listeners;

use App\Domains\Photography\Events\SessionCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DeductSessionCommissionListener implements ShouldQueue
{
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
