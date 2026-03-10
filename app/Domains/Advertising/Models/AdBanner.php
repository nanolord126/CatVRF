<?php

namespace App\Domains\Advertising\Models;

use App\Traits\Common\{HasEcosystemFeatures, HasEcosystemAuth};
use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};
use Illuminate\Support\Facades\Log;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * AdBanner Model - Рекламный баннер (Production 2026).
 * 
 * Представляет отдельный рекламный материал/креатив в составе кампании.
 * Может содержать одну или несколько медиа-файлов (изображения, видео).
 * 
 * Соответствует требованиям 347-ФЗ (обязательная маркировка ERID).
 */
class AdBanner extends Model implements HasMedia
{
    use HasEcosystemFeatures, HasEcosystemAuth, InteractsWithMedia;

    protected $table = 'ad_banners';
    protected $guarded = [];

    protected $casts = [
        'metadata' => 'array',
        'target_geo' => 'array',
        'is_active' => 'boolean',
        'is_ai_approved' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'marked_at' => 'datetime',
    ];

    protected $fillable = [
        'campaign_id',
        'placement_id',
        'type',                    // banner, video, native
        'name',
        'title',
        'description',
        'url',
        'target_geo',
        'erid',                     // ERID по 347-ФЗ
        'compliance_status',        // draft, moderation_failed_ai, registering, valid, expired
        'is_active',
        'is_ai_approved',
        'marked_at',
        'metadata',
        'correlation_id',
        'clicks',
    ];

    protected static function booted(): void
    {
        static::creating(function ($model) {
            $model->correlation_id = $model->correlation_id ?? \Illuminate\Support\Str::uuid()->toString();
        });
    }

    /**
     * Кампания, к которой относится баннер.
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(AdCampaign::class);
    }

    /**
     * Плейсмент (слот размещения) баннера.
     */
    public function placement(): BelongsTo
    {
        return $this->belongsTo(AdPlacement::class);
    }

    /**
     * Логи взаимодействий с баннером (клики, показы, просмотры).
     */
    public function interactions(): HasMany
    {
        return $this->hasMany(AdInteractionLog::class, 'ad_banner_id');
    }

    /**
     * Регистр ставок на показы баннера в аукционе.
     */
    public function auctionBids(): HasMany
    {
        return $this->hasMany(AdAuctionBid::class);
    }

    /**
     * Проверка, может ли баннер быть показан (active + compliant + не истек срок).
     */
    public function canBeShown(): bool
    {
        return $this->is_active
            && $this->compliance_status === 'valid'
            && !empty($this->erid);
    }

    /**
     * Инкрементить счётчик кликов (для быстрого счёта без loading полной модели).
     */
    public function incrementClicks(int $count = 1): int
    {
        return $this->increment('clicks', $count);
    }

    /**
     * Получить URL медиа для отправки в ОРД.
     */
    public function getMediaUrl(): ?string
    {
        $media = $this->getMedia('gallery')->first();
        return $media ? $media->getFullUrl() : null;
    }

    /**
     * Логирование действия с баннером в аудит трейл.
     */
    protected function logAudit(string $action, ?string $description = null): void
    {
        AuditLog::create([
            'action' => $action,
            'description' => $description ?? "Баннер {$action}",
            'model_type' => 'AdBanner',
            'model_id' => $this->id,
            'correlation_id' => $this->correlation_id,
            'metadata' => [
                'campaign_id' => $this->campaign_id,
                'compliance_status' => $this->compliance_status,
                'erid' => $this->erid,
            ],
        ]);
    }
}
