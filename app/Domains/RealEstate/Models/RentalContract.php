<?php declare(strict_types=1);

namespace App\Domains\RealEstate\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

final class RentalContract extends Model
{

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

    
}
