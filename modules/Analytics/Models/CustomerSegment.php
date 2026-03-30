<?php declare(strict_types=1);

namespace Modules\Analytics\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CustomerSegment extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected $fillable = [
            'user_id',
            'segment_type', // rfm, churn_risk, vip, interest
            'value',
            'score',
            'metadata',
        ];
    
        protected $casts = [
            'metadata' => 'array',
        ];
    
        public function user()
        {
            return $this->belongsTo(User::class);
        }
}
