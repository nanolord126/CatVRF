<?php declare(strict_types=1);

namespace App\Domains\Archived\HomeServices\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ContractorSchedule extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected $table = 'contractor_schedules';


        protected $fillable = ['tenant_id', 'contractor_id', 'day_of_week', 'start_time', 'end_time', 'is_available', 'correlation_id'];


        protected $hidden = [];


        protected $casts = ['is_available' => 'boolean', 'start_time' => 'time', 'end_time' => 'time'];


        protected static function booted(): void


        {


            static::addGlobalScope('tenant_id', fn($q) => $q->where('tenant_id', tenant('id')));


        }


        public function tenant(): BelongsTo { return $this->belongsTo(Tenant::class); }


        public function contractor(): BelongsTo { return $this->belongsTo(Contractor::class); }
}
