<?php declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

final class BonusTransaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'bonus_transactions';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'business_group_id',
        'wallet_id',
        'user_id',
        'type',
        'amount',
        'status',
        'source_type',
        'source_id',
        'correlation_id',
        'meta',
        'tags',
        'credited_at',
        'expires_at',
        'hold_until',
    ];

    protected $casts = [
        'amount' => 'integer',
        'meta' => 'json',
        'tags' => 'json',
        'credited_at' => 'datetime',
        'expires_at' => 'datetime',
        'hold_until' => 'datetime',
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_CREDITED = 'credited';
    public const STATUS_EXPIRED = 'expired';

    protected static function booted(): void
    {
        static::creating(function ($model) {
            $model->uuid = $model->uuid ?? (string) Str::uuid();
        });

        // Global scope tenant_id (Canon 2026)
        if (function_exists('tenant') && tenant('id')) {
            static::addGlobalScope('tenant_id', function ($builder) {
                $builder->where('tenant_id', tenant('id'));
            });
        }
    }
}
