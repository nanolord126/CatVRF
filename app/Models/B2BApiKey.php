<?php declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

final class B2BApiKey extends Model
{
    protected $table = 'b2b_api_keys';

    protected $fillable = [
        'business_group_id',
        'tenant_id',
        'uuid',
        'name',
        'key',
        'hashed_key',
        'permissions',
        'expires_at',
        'last_used_at',
        'last_ip',
        'is_active',
        'correlation_id',
        'tags',
    ];

    protected $casts = [
        'permissions'  => 'array',
        'tags'         => 'array',
        'is_active'    => 'boolean',
        'expires_at'   => 'datetime',
        'last_used_at' => 'datetime',
    ];

    /** Ключ никогда не выдаётся в ответе API. */
    protected $hidden = ['key', 'hashed_key'];

    protected static function booted(): void
    {
        static::creating(static function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid()->toString();
            }
        });
    }

    public function businessGroup(): BelongsTo
    {
        return $this->belongsTo(BusinessGroup::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function hasPermission(string $permission): bool
    {
        if (empty($this->permissions)) {
            return true; // null → все права
        }
        return in_array($permission, (array) $this->permissions, true);
    }
}
