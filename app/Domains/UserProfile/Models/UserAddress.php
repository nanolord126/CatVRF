<?php declare(strict_types=1);

namespace App\Domains\UserProfile\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Tenant;
use App\Models\User;

final class UserAddress extends Model
{
    protected $table = 'user_addresses';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'type',
        'address',
        'city',
        'region',
        'postal_code',
        'country',
        'lat',
        'lon',
        'is_default',
        'usage_count',
    ];

    protected $casts = [
        'lat' => 'float',
        'lon' => 'float',
        'is_default' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            $query->where('tenant_id', tenant()->id);
        });

        static::creating(function ($model) {
            if (!$model->uuid) {
                $model->uuid = \Illuminate\Support\Str::uuid()->toString();
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }
}
