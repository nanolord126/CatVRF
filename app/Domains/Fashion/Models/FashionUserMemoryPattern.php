<?php

declare(strict_types=1);

namespace App\Domains\Fashion\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

final class FashionUserMemoryPattern extends Model
{
    use TenantScoped;

    protected $table = 'fashion_user_memory_patterns';

    protected $fillable = [
        'user_id',
        'tenant_id',
        'pattern_type',
        'pattern_value',
        'confidence',
        'sample_size',
        'correlation_id',
    ];

    protected $casts = [
        'pattern_value' => 'array',
        'confidence' => 'decimal:2',
        'sample_size' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
