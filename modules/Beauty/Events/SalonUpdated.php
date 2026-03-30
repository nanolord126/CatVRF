<?php declare(strict_types=1);

namespace Modules\Beauty\Events;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class SalonUpdated extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use Dispatchable, InteractsWithSockets;
    
        public function __construct(public $salon)
        {
        }
    
        public function broadcastOn(): Channel
        {
            return new Channel('beauty');
        }
}
