<?php declare(strict_types=1);

namespace Modules\Advertising\Jobs;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class SendToOrdJob extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
        public $tries = 3; public $backoff = 60;
    
        public function __construct(protected Creative $creative) {}
    
        public function handle(OrdService $ord): void {
            if ($erid = $ord->getErid($this->creative)) {
                $this->creative->update(['erid' => $erid]);
            }
        }
}
