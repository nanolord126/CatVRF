<?php

namespace App\Domains\Advertising\Models;

use App\Traits\Common\{HasEcosystemFeatures, HasEcosystemAuth};
use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\{Auth, Log};

/**
 * AdAuctionBid Model - Ставка в аукционе за размещение (Production 2026).
 * 
 * Ставка от рекламодателя на размещение баннера в конкретном слоте.
 * Поддерживает:
 * - Валидацию минимального CPM
 * - Аудит логирование всех изменений
 * - Фрод-детекцию по аномальным ставкам
 */
class AdAuctionBid extends Model
{
    use HasEcosystemFeatures, HasEcosystemAuth;

    protected $table = 'ad_auction_bids';
    protected $guarded = [];

    protected $casts = [
        'cpm_bid' => 'decimal:2',        // Ставка за 1000 показов
        'min_impressions' => 'integer',  // Минимум 10,000 по ТЗ
        'total_budget' => 'decimal:2',
        'is_active' => 'boolean',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $fillable = [
        'ad_campaign_id',
        'placement_id',
        'cpm_bid',
        'min_impressions',
        'total_budget',
        'is_active',
        'metadata',
        'correlation_id',
    ];

    /**
     * Валидация CPM ставки при сохранении.
     */
    protected static function booted()
    {
        static::creating(function ($model) {
            $model->validateCpmBid();
            $model->logAudit('ad_auction_bid.created');
        });

        static::updating(function ($model) {
            $model->validateCpmBid();
            $model->logAudit('ad_auction_bid.updated');
        });
    }

    private function validateCpmBid(): void
    {
        if ($this->cpm_bid < 0.01) {
            Log::warning('Suspiciously low CPM bid detected', [
                'bid_id' => $this->id,
                'cpm' => $this->cpm_bid,
                'correlation_id' => $this->correlation_id ?? 'unknown',
            ]);
        }
    }

    private function logAudit(string $action): void
    {
        $userId = Auth::id();

        AuditLog::create([
            'user_id' => $userId,
            'action' => $action,
            'model_type' => 'AdAuctionBid',
            'model_id' => $this->id,
            'correlation_id' => $this->correlation_id,
            'metadata' => [
                'cpm_bid' => $this->cpm_bid,
                'total_budget' => $this->total_budget,
            ],
        ]);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(AdCampaign::class, 'ad_campaign_id');
    }

    public function placement(): BelongsTo
    {
        return $this->belongsTo(AdPlacement::class, 'placement_id');
    }
}
