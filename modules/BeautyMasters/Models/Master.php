<?php declare(strict_types=1);

namespace Modules\BeautyMasters\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Master extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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
