<?php

declare(strict_types=1);

namespace App\Domains\CRM\Models;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CrmTaxiProfile — CRM-профиль клиента вертикали Такси.
 *
 * Маршруты, предпочтения, корпоративные поездки, рейтинг.
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 */
final class CrmTaxiProfile extends Model
{
use \Illuminate\Database\Eloquent\SoftDeletes;

    use HasFactory;

    protected static function newFactory(): \Database\Factories\CRM\CrmTaxiProfileFactory
    {
        return \Database\Factories\CRM\CrmTaxiProfileFactory::new();
    }
    protected $table = 'crm_taxi_profiles';

    protected $fillable = [
        'crm_client_id', 'tenant_id', 'uuid', 'frequent_routes', 'home_address',
        'work_address', 'saved_addresses', 'preferred_car_class',
        'preferred_payment', 'is_corporate', 'corporate_account_id',
        'monthly_ride_budget', 'total_rides', 'total_spent_rides',
        'avg_rating_given', 'preferred_drivers', 'ride_time_patterns',
        'needs_child_seat', 'needs_pet_friendly', 'notes', 'correlation_id',
    ];

    protected $casts = [
        'frequent_routes' => 'json',
        'home_address' => 'json',
        'work_address' => 'json',
        'saved_addresses' => 'json',
        'preferred_drivers' => 'json',
        'ride_time_patterns' => 'json',
        'is_corporate' => 'boolean',
        'needs_child_seat' => 'boolean',
        'needs_pet_friendly' => 'boolean',
        'monthly_ride_budget' => 'decimal:2',
        'total_spent_rides' => 'decimal:2',
        'avg_rating_given' => 'decimal:2',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(CrmClient::class, 'crm_client_id');
    }

    public function __toString(): string
    {
        return sprintf('CrmTaxiProfile[id=%d, class=%s]', $this->id ?? 0, $this->preferred_car_class ?? '');
    }
}
