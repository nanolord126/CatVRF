<?php

declare(strict_types=1);

namespace App\Domains\RealEstate\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class RentalContract extends Model
{
    use TenantScoped;

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

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    public function tenantUser(): BelongsTo
    {
        // Предполагается связь с таблицей пользователей
        return $this->belongsTo(User::class, 'tenant_user_id');
    }

    protected static function booted(): void
    {
        self::creating(function (RentalContract $model) {
            $model->uuid = $model->uuid ?? (string) Str::uuid();
            if (empty($model->tenant_id) && function_exists('tenant') && tenant()) {
                $model->tenant_id = tenant()->id;
            }
        });
    }
}
