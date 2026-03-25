declare(strict_types=1);

<?php

namespace Modules\BeautyMasters\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Master
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class Master extends Model
{
    protected $table = 'beauty_masters';
    protected $fillable = ['name', 'category', 'portfolio'];
    protected $casts = ['portfolio' => 'array'];

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }
}

class Service extends Model
{
    protected $table = 'beauty_services';
    protected $fillable = ['name', 'price', 'duration_minutes'];
}

class Appointment extends Model
{
    protected $table = 'master_appointments';
    protected $fillable = ['master_id', 'service_id', 'starts_at', 'ends_at', 'status'];

    public function master()
    {
        return $this->belongsTo(Master::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}
