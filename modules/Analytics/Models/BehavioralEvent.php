<?php declare(strict_types=1);

namespace Modules\Analytics\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class BehavioralEvent extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasUuids;
    
        protected $fillable = [
            'user_id',
            'event_type',
            'vertical',
            'target_id',
            'payload',
            'monetary_value',
            'correlation_id',
            'occurred_at',
        ];
    
        protected $casts = [
            'payload' => 'array',
            'occurred_at' => 'datetime',
            'monetary_value' => 'decimal:2',
        ];
    
        public function user()
        {
            return $this->belongsTo(User::class);
        }
}
