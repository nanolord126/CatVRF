<?php

declare(strict_types=1);

namespace App\Domains\Notifications\Models;

use Carbon\CarbonImmutable;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Str;

final class NotificationPreference extends Model
{
    protected $fillable = [
        'tenant_id',
        'user_id',
        'channel',
        'enabled',
        'quiet_hours_start',
        'quiet_hours_end',
    ];

    protected $casts = [
        'enabled' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isInQuietHours(): bool
    {
        if (! $this->quiet_hours_start || ! $this->quiet_hours_end) {
            return false;
        }

        $now = CarbonImmutable::now()->format('H:i');

        return $now >= $this->quiet_hours_start && $now <= $this->quiet_hours_end;
    }

    public function shouldSend(): bool
    {
        return $this->enabled && ! $this->isInQuietHours();
    }

    protected static function booted(): void
    {
        self::addGlobalScope('tenant', function ($query) {
            $query->where('tenant_id', tenant()->id);
        });

        self::creating(function ($model) {
            if (! $model->uuid) {
                $model->uuid = Str::uuid()->toString();
            }
        });
    }
}
