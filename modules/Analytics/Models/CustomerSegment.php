<?php

namespace Modules\Analytics\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

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
