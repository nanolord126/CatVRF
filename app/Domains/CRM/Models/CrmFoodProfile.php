<?php

declare(strict_types=1);

namespace App\Domains\CRM\Models;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CrmFoodProfile — CRM-профиль клиента вертикали Еда/Рестораны.
 *
 * Диеты, аллергены, любимые блюда, КБЖУ, корпоративное питание.
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 */
final class CrmFoodProfile extends Model
{

    use HasFactory, TenantScoped;

    protected static function newFactory(): \Database\Factories\CRM\CrmFoodProfileFactory
    {
        return \Database\Factories\CRM\CrmFoodProfileFactory::new();
    }
    protected $table = 'crm_food_profiles';

    protected $fillable = [
        'crm_client_id', 'tenant_id', 'uuid', 'dietary_restrictions', 'allergies',
        'favorite_cuisines', 'favorite_dishes', 'disliked_ingredients',
        'preferred_spiciness', 'daily_calorie_target', 'macros_target',
        'meal_plan_type', 'avg_order_frequency_days', 'avg_order_amount',
        'delivery_time_preferences', 'is_corporate_client', 'corporate_headcount',
        'corporate_schedule', 'notes', 'correlation_id',
    ];

    protected $casts = [
        'dietary_restrictions' => 'json',
        'allergies' => 'json',
        'favorite_cuisines' => 'json',
        'favorite_dishes' => 'json',
        'disliked_ingredients' => 'json',
        'macros_target' => 'json',
        'delivery_time_preferences' => 'json',
        'corporate_schedule' => 'json',
        'is_corporate_client' => 'boolean',
        'avg_order_amount' => 'decimal:2',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(CrmClient::class, 'crm_client_id');
    }

    public function __toString(): string
    {
        return sprintf('CrmFoodProfile[id=%d]', $this->id ?? 0);
    }
}
