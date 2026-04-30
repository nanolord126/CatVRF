<?php

declare(strict_types=1);

namespace App\Domains\Leisure\SubVerticals\Tickets\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Models\User;

final class CheckInLog extends Model
{
    use TenantScoped;

    protected $table = 'check_in_logs';

    protected $fillable = [
        'uuid', 'tenant_id', 'ticket_id', 'checker_user_id',
        'ip_address', 'device_info', 'is_success',
        'error_reason', 'location', 'correlation_id',
    ];

    protected $casts = [
        'is_success' => 'boolean',
        'location' => 'json',
        'device_info' => 'json',
    ];

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
        return $this->belongsTo(User::class, 'checker_user_id');
    }

    protected static function booted(): void
    {
        self::addGlobalScope('tenant', function ($builder) {
            if (function_exists('tenant') && tenant()?->id) {
                $builder->where('tenant_id', tenant()?->id);
            }
        });

        self::creating(function ($model) {
            $model->uuid = (string) Str::uuid();
            if (empty($model->tenant_id) && function_exists('tenant')) {
                $model->tenant_id = tenant()?->id;
            }
        });
    }
}
