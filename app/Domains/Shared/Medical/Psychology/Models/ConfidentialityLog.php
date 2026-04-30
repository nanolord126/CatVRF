<?php

declare(strict_types=1);

namespace App\Domains\Shared\Medical\Psychology\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Carbon\CarbonImmutable;

final class ConfidentialityLog extends Model
{
    public $timestamps = false; // Юзаем только created_at по дефолту


    protected $table = 'psy_confidentiality_logs';

    protected $fillable = [
        'uuid',
        'correlation_id',
        'tenant_id',
        'user_id',
        'session_id',
        'action',
        'ip_address',
        'reason',
        'correlation_id',
        'created_at',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(PsychologicalSession::class, 'session_id');
    }

    protected static function booted_disabled(): void
    {
        self::addGlobalScope('tenant', function (Builder $builder) {
            if (function_exists('tenant') && tenant()) {
                $builder->where('tenant_id', tenant()->id);
            }
        });

        self::creating(function (self $model) {
            $model->correlation_id = (string) Str::uuid();
            $model->tenant_id = tenant()->id ?? 0;
            $model->ip_address = $this->request->ip();
            $model->created_at = CarbonImmutable::now();
        });
    }
}
