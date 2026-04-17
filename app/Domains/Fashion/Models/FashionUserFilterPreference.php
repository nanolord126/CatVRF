<?php declare(strict_types=1);

namespace App\Domains\Fashion\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class FashionUserFilterPreference extends Model
{
    protected $table = 'fashion_user_filter_preferences';
    protected $fillable = [
        'user_id',
        'tenant_id',
        'preferred_filters',
        'correlation_id',
    ];
    protected $casts = [
        'preferred_filters' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }
}
