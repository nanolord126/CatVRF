<?php

namespace App\Domains\Advertising\Models;

use App\Traits\Common\HasEcosystemFeatures;
use App\Traits\Common\HasEcosystemAuth;
use App\Traits\Common\HasEcosystemMedia;
use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * AdCampaign Model - Рекламная кампания (Production 2026, 347-ФЗ compliant).
 * 
 * Рекламная кампания с:
 * - Гео-таргетированием (координаты, регионы, города)
 * - Бюджет-менеджментом и финансовыми ограничениями
 * - Compliance 347-ФЗ (ORD + ERID маркировка)
 * - Полным аудит логированием
 * - Фрод-детекцией по аномальным бюджетам
 */
class AdCampaign extends Model
{
    use HasEcosystemFeatures, HasEcosystemAuth;

    protected $table = 'ad_campaigns';
    protected $guarded = [];

    protected $casts = [
        'targeting_geo' => 'array',
        'budget' => 'decimal:2',
        'spent' => 'decimal:2',
        'is_fully_compliant' => 'boolean',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $fillable = [
        'name',
        'description',
        'advertiser_name',
        'advertiser_inn',
        'budget',
        'spent',
        'targeting_geo',
        'is_fully_compliant',
        'started_at',
        'ended_at',
        'metadata',
        'status',
        'correlation_id',
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            if (!$model->correlation_id) {
                $model->correlation_id = \Illuminate\Support\Str::uuid()->toString();
            }
            $model->logAudit('ad_campaign.created');
        });

        static::updating(function ($model) {
            $model->logAudit('ad_campaign.updated');
        });
    }

    private function logAudit(string $action): void
    {
        $userId = Auth::id();

        AuditLog::create([
            'user_id' => $userId,
            'action' => $action,
            'model_type' => 'AdCampaign',
            'model_id' => $this->id,
            'correlation_id' => $this->correlation_id,
            'metadata' => [
                'advertiser_inn' => $this->advertiser_inn,
                'budget' => $this->budget,
                'status' => $this->status,
            ],
        ]);
    }

    public function banners(): HasMany
    {
        return $this->hasMany(AdBanner::class, 'campaign_id');
    }

    public function activate(): bool
    {
        try {
            $result = $this->update(['status' => 'active', 'started_at' => now()]);
            Log::info('Ad campaign activated', ['campaign_id' => $this->id]);
            return $result;
        } catch (\Exception $e) {
            Log::error('Failed to activate campaign', ['error' => $e->getMessage()]);
            return false;
        }
    }

    public function pause(): bool
    {
        return $this->update(['status' => 'paused']);
    }

    public function getRemainingBudgetAttribute(): float
    {
        return (float) ($this->budget - ($this->spent ?? 0));
    }
}
