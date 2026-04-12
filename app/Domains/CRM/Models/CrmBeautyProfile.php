<?php

declare(strict_types=1);

namespace App\Domains\CRM\Models;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CRM Beauty Profile — медицинская карта и предпочтения клиента салона красоты.
 * Аллергии, тип кожи, предпочтения мастеров, фото «до/после».
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 */
final class CrmBeautyProfile extends Model
{
use \Illuminate\Database\Eloquent\SoftDeletes;

    use HasFactory;

    protected static function newFactory(): \Database\Factories\CRM\CrmBeautyProfileFactory
    {
        return \Database\Factories\CRM\CrmBeautyProfileFactory::new();
    }
    protected $table = 'crm_beauty_profiles';

    protected $fillable = [
        'tenant_id',
        'uuid',
        'crm_client_id',
        'correlation_id',
        'allergies',
        'skin_type',
        'contraindications',
        'hair_type',
        'hair_color',
        'face_shape',
        'preferred_masters',
        'preferred_services',
        'favorite_products',
        'before_after_photos',
        'birthday',
        'special_dates',
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
        'allergies' => 'json',
        'contraindications' => 'json',
        'preferred_masters' => 'json',
        'preferred_services' => 'json',
        'favorite_products' => 'json',
        'before_after_photos' => 'json',
        'special_dates' => 'json',
        'birthday' => 'date',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(CrmClient::class, 'crm_client_id');
    }

    /**
     * Проверяет, есть ли у клиента аллергия на указанное вещество.
     */
    public function hasAllergy(string $substance): bool
    {
        $allergies = $this->allergies ?? [];

        return in_array($substance, $allergies, true);
    }

    /**
     * Добавляет фото «до/после» в историю.
     */
    public function addBeforeAfterPhoto(string $beforeUrl, string $afterUrl, string $serviceName, string $date): void
    {
        $photos = $this->before_after_photos ?? [];

        $photos[] = [
            'date' => $date,
            'before_url' => $beforeUrl,
            'after_url' => $afterUrl,
            'service' => $serviceName,
        ];

        $this->update(['before_after_photos' => $photos]);
    }
}
