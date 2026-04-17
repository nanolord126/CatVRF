<?php declare(strict_types=1);

namespace App\Domains\Notifications\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Tenant;
use App\Models\User;

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

    public function isInQuietHours(): bool
    {
        if (!$this->quiet_hours_start || !$this->quiet_hours_end) {
            return false;
        }

        $now = now()->format('H:i');
        return $now >= $this->quiet_hours_start && $now <= $this->quiet_hours_end;
    }

    public function shouldSend(): bool
    {
        return $this->enabled && !$this->isInQuietHours();
    }
}
