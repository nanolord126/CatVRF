declare(strict_types=1);

<?php

declare(strict_types=1);

namespace App\Domains\Photography\Jobs;

use App\Domains\Photography\Models\PhotoSession;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * UpdateSessionStatusJob
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class UpdateSessionStatusJob implements ShouldQueue
{
	use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

	public int $tries = 3;
	public int $timeout = 60;

	public function __construct(
		public readonly ?PhotoSession $session = null,
		public readonly string $newStatus = '',
		public readonly string $correlationId = '',
	) {
    /**
     * Инициализировать класс
     */
    public function __construct()
    {
        // TODO: инициализация
    }
}

	public function handle(): void
	{
		try {
			$this->db->transaction(function () {
				$this->session->update(['status' => $this->newStatus]);

				$this->log->channel('audit')->info('Photography: Session status auto-updated', [
					'session_id' => $this->session->id,
					'new_status' => $this->newStatus,
					'correlation_id' => $this->correlationId,
				]);
			});
		} catch (\Exception $e) {
			$this->log->channel('audit')->error('Photography: Session status update failed', [
				'session_id' => $this->session->id,
				'error' => $e->getMessage(),
				'correlation_id' => $this->correlationId,
			]);
			throw $e;
		}
	}
}
