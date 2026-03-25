declare(strict_types=1);

<?php

namespace Modules\BeautyMasters\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Appointment
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class Appointment extends Model
{
    protected $fillable = ['master_id', 'service_name', 'client_name', 'start_time', 'end_time', 'status'];

    public function master()
    {
        return $this->belongsTo(Master::class);
    }
}
