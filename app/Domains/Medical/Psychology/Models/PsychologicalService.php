<?php

declare(strict_types=1);

namespace App\Domains\Medical\Psychology\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * Психологическая услуга.
 */
final class PsychologicalService extends Model
{
    protected $table = 'psy_services';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'psychologist_id',
        'name',
        'duration_minutes',
        'price',
        'delivery_type',
        'description',
        'tags',
        'correlation_id',
    ];

    protected $casts = [
        'price' => 'integer',
        'duration_minutes' => 'integer',
        'tags' => 'json',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            if (auth()->check()) {
                $builder->where('tenant_id', auth()->user()->tenant_id);
            }
        });

        static::creating(function (self $model) {
            $model->uuid = (string) Str::uuid();
            $model->correlation_id = request()->header('X-Correlation-ID', (string) Str::uuid());
            $model->tenant_id = auth()->user()->tenant_id ?? 0;
        });
    }

    public function psychologist(): BelongsTo
    {
        return $this->belongsTo(Psychologist::class, 'psychologist_id');
    }
}
