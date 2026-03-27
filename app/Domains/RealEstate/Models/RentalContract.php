<?php

declare(strict_types=1);

namespace App\Domains\RealEstate\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Support\Str;

final class RentalContract extends Model
{
    use LogsActivity;

    protected $table = 'rental_contracts';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'listing_id',
        'tenant_user_id',
        'correlation_id',
        'start_date',
        'end_date',
        'monthly_rent',
        'paid_deposit',
        'contract_status',
        'terms',
    ];

    protected $casts = [
        'terms' => 'json',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'monthly_rent' => 'integer',
        'paid_deposit' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (RentalContract $model) {
            $model->uuid = $model->uuid ?? (string) Str::uuid();
            if (empty($model->tenant_id) && function_exists('tenant') && tenant()) {
                $model->tenant_id = tenant()->id;
            }
        });

        static::addGlobalScope('tenant', function (Builder $builder) {
             if (function_exists('tenant') && tenant()) {
                $builder->where('tenant_id', tenant()->id);
            }
        });
    }

    public function listing(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    public function tenantUser(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        // Предполагается связь с таблицей пользователей
        return $this->belongsTo(\App\Models\User::class, 'tenant_user_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->useLogName('rental_contracts')
            ->logOnlyDirty();
    }
}
