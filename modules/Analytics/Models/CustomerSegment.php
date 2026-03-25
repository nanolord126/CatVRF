declare(strict_types=1);

<?php

namespace Modules\Analytics\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

/**
 * CustomerSegment
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class CustomerSegment extends Model
{
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
