<?php

declare(strict_types=1);

namespace App\Domains\Tickets\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Support\Str;

/**
 * КАНОН 2026: Логи чекина (прохода по QR).
 * Слой 2: Модели.
 */
final class CheckInLog extends Model
{
    use LogsActivity;

    protected $table = 'check_in_logs';

    protected $fillable = [
        'uuid', 'tenant_id', 'ticket_id', 'checker_user_id', 
        'ip_address', 'device_info', 'is_success', 
        'error_reason', 'location', 'correlation_id'
    ];

    protected $casts = [
        'is_success' => 'boolean',
        'location' => 'json',
        'device_info' => 'json'
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($builder) {
            if (function_exists('tenant') && tenant('id')) {
                $builder->where('tenant_id', tenant('id'));
            }
        });

        static::creating(function ($model) {
            $model->uuid = (string) Str::uuid();
            if (empty($model->tenant_id) && function_exists('tenant')) {
                $model->tenant_id = tenant('id');
            }
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['ticket_id', 'checker_user_id', 'is_success', 'error_reason'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('audit');
    }

    /**
     * Билет лога.
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /**
     * Кто проверял.
     */
    public function checker(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'checker_user_id');
    }
}
