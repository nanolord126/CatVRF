<?php declare(strict_types=1);

namespace App\Domains\Archived\Photography\Jobs;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class UpdateSessionStatusJob extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    	public int $tries = 3;


    	public int $timeout = 60;


    	public function __construct(


    		public readonly ?PhotoSession $session = null,


    		public readonly string $newStatus = '',


    		public readonly string $correlationId = '',


    	) {}


    	public function handle(): void


    	{


    		try {


    			DB::transaction(function () {


    				$this->session->update(['status' => $this->newStatus]);


    				Log::channel('audit')->info('Photography: Session status auto-updated', [


    					'session_id' => $this->session->id,


    					'new_status' => $this->newStatus,


    					'correlation_id' => $this->correlationId,


    				]);


    			});


    		} catch (\Exception $e) {


    			Log::channel('audit')->error('Photography: Session status update failed', [


    				'session_id' => $this->session->id,


    				'error' => $e->getMessage(),


    				'correlation_id' => $this->correlationId,


    			]);


    			throw $e;


    		}


    	}
}
