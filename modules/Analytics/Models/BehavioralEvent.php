<?php

namespace Modules\Analytics\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Models\User;

class BehavioralEvent extends Model
{
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
