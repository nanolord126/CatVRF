<?php

declare(strict_types=1);

namespace App\Domains\CRM\Models;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CRM Hotel Guest Profile — профиль гостя отеля.
 * Предпочтения номера, особые отметки, история проживаний.
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 */
final class CrmHotelGuestProfile extends Model
{
use \Illuminate\Database\Eloquent\SoftDeletes;

    use HasFactory;

    protected static function newFactory(): \Database\Factories\CRM\CrmHotelGuestProfileFactory
    {
        return \Database\Factories\CRM\CrmHotelGuestProfileFactory::new();
    }
    protected $table = 'crm_hotel_guest_profiles';

    protected $fillable = [
        'tenant_id',
        'uuid',
        'crm_client_id',
        'correlation_id',
        'preferred_room_type',
        'preferred_floor',
        'preferred_view',
        'preferred_amenities',
        'is_smoking',
        'has_pets',
        'is_vip_service',
        'dietary_restrictions',
        'allergies',
        'preferred_language',
        'passport_country',
        'frequent_guest_number',
        'birthday',
        'special_dates',
        'average_review_rating',
        'total_stays',
        'total_nights',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', static function ($query) {
            if (app()->has('tenant') && tenant()) {
                $query->where('tenant_id', tenant()->id);
            }
        });
    }

    protected $casts = [
        'preferred_amenities' => 'json',
        'dietary_restrictions' => 'json',
        'allergies' => 'json',
        'special_dates' => 'json',
        'is_smoking' => 'boolean',
        'has_pets' => 'boolean',
        'is_vip_service' => 'boolean',
        'average_review_rating' => 'decimal:2',
        'total_stays' => 'integer',
        'total_nights' => 'integer',
        'birthday' => 'date',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(CrmClient::class, 'crm_client_id');
    }

    /**
     * Увеличивает счётчик проживаний.
     */
    public function recordStay(int $nights): void
    {
        $this->increment('total_stays');
        $this->increment('total_nights', $nights);
    }

    /**
     * Обновляет средний рейтинг отзывов.
     */
    public function updateAverageRating(float $newRating): void
    {
        $totalStays = $this->total_stays ?: 1;
        $currentTotal = $this->average_review_rating * ($totalStays - 1);
        $newAverage = ($currentTotal + $newRating) / $totalStays;

        $this->update(['average_review_rating' => round($newAverage, 2)]);
    }
}
