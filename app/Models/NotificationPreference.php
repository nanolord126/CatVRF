<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * NotificationPreference - предпочтения пользователя по уведомлениям
 * 
 * Позволяет пользователям управлять:
 * - Какие типы уведомлений они получают
 * - Какие каналы доставки они используют
 * - Частоту уведомлений
 */
final class NotificationPreference extends Model
{
    protected $table = 'notification_preferences';

    protected $fillable = [
        'user_id',
        'tenant_id',
        'notification_type',
        'enabled',
        'channel_email',
        'channel_sms',
        'channel_push',
        'channel_database',
        'quiet_hours_enabled',
        'quiet_hours_start',
        'quiet_hours_end',
        'max_per_day',
        'frequency', // 'immediate', 'daily_digest', 'weekly_digest'
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'channel_email' => 'boolean',
        'channel_sms' => 'boolean',
        'channel_push' => 'boolean',
        'channel_database' => 'boolean',
        'quiet_hours_enabled' => 'boolean',
        'quiet_hours_start' => 'string',
        'quiet_hours_end' => 'string',
        'max_per_day' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Отношение к пользователю
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Отношение к тенанту
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Сейчас в quiet hours?
     */
    public function isInQuietHours(): bool
    {
        if (!$this->quiet_hours_enabled) {
            return false;
        }

        $now = now()->format('H:i');
        $start = $this->quiet_hours_start;
        $end = $this->quiet_hours_end;

        // Если start < end (нормальный диапазон)
        if ($start < $end) {
            return $now >= $start && $now <= $end;
        }

        // Если start > end (диапазон через полночь)
        return $now >= $start || $now <= $end;
    }

    /**
     * Получить активные каналы
     */
    public function getActiveChannels(): array
    {
        $channels = [];
        
        if ($this->channel_email) $channels[] = 'mail';
        if ($this->channel_sms) $channels[] = 'sms';
        if ($this->channel_push) $channels[] = 'push';
        if ($this->channel_database) $channels[] = 'database';

        return !empty($channels) ? $channels : ['database'];
    }

    /**
     * Переключить канал
     */
    public function toggleChannel(string $channel): void
    {
        $columnName = 'channel_' . $channel;
        if (in_array($columnName, $this->fillable)) {
            $this->update([
                $columnName => !$this->getAttribute($columnName),
            ]);
        }
    }

    /**
     * Отключить все каналы
     */
    public function disableAllChannels(): void
    {
        $this->update([
            'enabled' => false,
            'channel_email' => false,
            'channel_sms' => false,
            'channel_push' => false,
            'channel_database' => false,
        ]);
    }

    /**
     * Включить все каналы
     */
    public function enableAllChannels(): void
    {
        $this->update([
            'enabled' => true,
            'channel_email' => true,
            'channel_sms' => true,
            'channel_push' => true,
            'channel_database' => true,
        ]);
    }
}
