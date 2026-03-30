<?php declare(strict_types=1);

namespace Modules\BeautyMasters\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Appointment extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected $fillable = ['master_id', 'service_name', 'client_name', 'start_time', 'end_time', 'status'];
    
        public function master()
        {
            return $this->belongsTo(Master::class);
        }
}
