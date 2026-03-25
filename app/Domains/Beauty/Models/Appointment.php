<?php declare(strict_types=1);

namespace App\Domains\Beauty\Models;

use App\Domains\Beauty\Enums\CancellationPolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Модель записи на услугу.
 * Production 2026.
 */
final class Appointment extends Model
{
    use HasUuids, SoftDeletes;

    public const STATUS_PENDING   = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_NO_SHOW   = 'no-show';

    protected $table = 'appointments';

    protected $fillable = [
        'tenant_id',
        'salon_id',
        'master_id',
        'service_id',
        'client_id',
        'is_group',
        'group_size',
        'group_leader_id',
        'group_name',
        'is_wedding_event', // ✅ Wedding
        'is_photo_session', // ✅ Photo Session
        'photo_session_type',
        'photographer_id',
        'photo_location',
        'photo_concept',
        'appointment_type', // ✅ Unified 2026 Fields
        'cancellation_policy',
        'reschedule_policy',
        'is_group',
        'group_size',
        'is_kids_party',
        'kids_count',
        'is_corporate_event',
        'corporate_client_id',
        'is_luxury_service',
        'is_ai_constructed',
        'location_type',
        'outdoor_address',
        'subscription_id',
        'gift_certificate_id',
        'is_force_majeure',   // 13.0 FORCE MAJEURE
        'force_majeure_type',
        'force_majeure_party',
        'force_majeure_proof',
        'force_majeure_at',
        'cancelled_by',
        'compensation_amount',
        'compensation_type',
        'business_penalty_amount',
        'client_compensation_bonus',
        'business_penalty_status',
        'is_unjustified_cancellation',
        'business_cancelled_at',
        'wedding_date',
        'bride_name',
        'wedding_package_type',
        'number_of_guests_involved',
        'is_wedding_group', // Deprecated but kept for compatibility
        'group_type',
        'is_kids_party',
        'kids_count',
        'age_range',
        'has_allergies',
        'allergies_info',
        'party_theme',
        'is_corporate_event',
        'corporate_client_id',
        'company_name',
        'event_type',
        'participants_count',
        'contract_number',
        'datetime_start',
        'status',
        'cancellation_policy',
        'price',
        'price_cents',
        'payment_status',
        'correlation_id',
        'tags',
        'metadata',
    ];

    protected $hidden = [];

    protected $casts = [
        'is_group' => 'boolean',
        'group_size' => 'integer',
        'is_force_majeure' => 'boolean', // ✅ FM
        'force_majeure_type' => \App\Enums\ForceMajeureType::class,
        'force_majeure_party' => \App\Enums\ForceMajeureParty::class,
        'force_majeure_proof' => 'array',
        'force_majeure_at' => 'datetime',
        'cancellation_policy' => \App\Enums\AppointmentCancellationPolicy::class,
        'datetime_start' => 'datetime',
        'tags' => 'collection',
        'metadata' => 'json',
        'price' => 'integer',
        'price_cents' => 'integer',
        'compensation_amount' => 'integer',
        'business_penalty_amount' => 'integer',
        'client_compensation_bonus' => 'integer',
        'is_unjustified_cancellation' => 'boolean',
        'business_cancelled_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', fn ($query) => $query->where('tenant_id', tenant('id') ?? 0));
    }

    public function salon(): BelongsTo
    {
        return $this->belongsTo(BeautySalon::class, 'salon_id');
    }

    public function master(): BelongsTo
    {
        return $this->belongsTo(Master::class, 'master_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(BeautyService::class, 'service_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(related: \App\Models\User::class, foreignKey: 'client_id');
    }

    public function consumables(): HasMany
    {
        return $this->hasMany(BeautyConsumable::class, 'appointment_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'appointment_id');
    }
}
