<?php

namespace App\Domains\Advertising\Models;

use App\Traits\Common\HasEcosystemFeatures;
use App\Traits\Common\HasEcosystemAuth;
use App\Models\AuditLog;
use App\Domains\Advertising\Models\AdBanner;
use App\Domains\Advertising\Models\AdPlacement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * AdInteractionLog Model - Лог взаимодействий пользователей с рекламой.
 * 
 * Отслеживает:
 * - Показы баннеров (impressions)
 * - Клики (clicks)
 * - Видео просмотры (video_play)
 * - Форм сабмиты (form_submit)
 * - Потенциальный фрод (координаты, IP, user agent)
 */
class AdInteractionLog extends Model
{
    use HasEcosystemFeatures, HasEcosystemAuth;

    protected $table = 'ad_interaction_logs';
    protected $guarded = [];
    protected string $correlationId;

    protected $casts = [
        'point_x' => 'integer',           // X координата клика на баннере
        'point_y' => 'integer',           // Y координата клика на баннере
        'metadata' => 'array',            // Доп. данные (device, referer, utm и т.д.)
        'interacted_at' => 'datetime',
        'is_fraud_suspected' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $fillable = [
        'ad_banner_id',
        'placement_id',
        'user_id',
        'event_type',           // impression, click, video_play, form_submit
        'point_x',
        'point_y',
        'ip_address',
        'user_agent',
        'referer',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'is_fraud_suspected',
        'fraud_score',
        'metadata',
        'interacted_at',
        'correlation_id',
    ];

    protected static function booted(): void
    {
        static::creating(function ($model) {
            $model->correlation_id = $model->correlation_id ?? \Illuminate\Support\Str::uuid()->toString();
        });
    }

    /**
     * Получить баннер, с которым произошло взаимодействие.
     */
    public function banner(): BelongsTo
    {
        return $this->belongsTo(AdBanner::class, 'ad_banner_id');
    }

    /**
     * Получить плейсмент, в котором произошло взаимодействие.
     */
    public function placement(): BelongsTo
    {
        return $this->belongsTo(AdPlacement::class, 'placement_id');
    }

    /**
     * Логирование взаимодействия с аудит трейлом и обнаружением фрода (Production 2026).
     * 
     * @param AdBanner $banner
     * @param AdPlacement $placement
     * @param string $eventType impression|click|video_play|form_submit
     * @param array $data Дополнительные данные (ip_address, user_agent, coordinates и т.д.)
     * @return self
     * @throws \InvalidArgumentException Если eventType не валиден
     */
    public static function logInteraction(
        AdBanner $banner,
        AdPlacement $placement,
        string $eventType,
        array $data = []
    ): self {
        // Валидация типа события
        $allowedEventTypes = ['impression', 'click', 'video_play', 'form_submit'];
        if (!in_array($eventType, $allowedEventTypes)) {
            throw new \InvalidArgumentException(
                "Invalid event_type '{$eventType}'. Allowed: " . implode(', ', $allowedEventTypes)
            );
        }

        try {
            // Проверка на fraud-признаки
            $fraudScore = self::calculateFraudScore($data);
            $isFraudSuspected = $fraudScore > 60;

            $correlationId = \Illuminate\Support\Str::uuid()->toString();

            $interaction = self::create([
                'ad_banner_id' => $banner->id,
                'placement_id' => $placement->id,
                'event_type' => $eventType,
                'ip_address' => $data['ip_address'] ?? null,
                'user_agent' => $data['user_agent'] ?? null,
                'referer' => $data['referer'] ?? null,
                'utm_source' => $data['utm_source'] ?? null,
                'utm_medium' => $data['utm_medium'] ?? null,
                'utm_campaign' => $data['utm_campaign'] ?? null,
                'point_x' => $data['point_x'] ?? null,
                'point_y' => $data['point_y'] ?? null,
                'is_fraud_suspected' => $isFraudSuspected,
                'fraud_score' => $fraudScore,
                'metadata' => $data['metadata'] ?? [],
                'interacted_at' => $data['interacted_at'] ?? now(),
                'correlation_id' => $correlationId,
            ]);

            // Логирование в audit trail
            AuditLog::create([
                'action' => "advertising.banner_interaction_{$eventType}",
                'description' => "Взаимодействие с баннером: {$eventType}",
                'model_type' => 'AdInteractionLog',
                'model_id' => $interaction->id,
                'correlation_id' => $correlationId,
                'metadata' => [
                    'banner_id' => $banner->id,
                    'placement_id' => $placement->id,
                    'is_fraud_suspected' => $isFraudSuspected,
                    'fraud_score' => $fraudScore,
                    'ip_address' => $data['ip_address'] ?? null,
                    'event_type' => $eventType,
                ],
            ]);

            // Логирование подозрений на фрод
            if ($isFraudSuspected) {
                Log::warning('Suspected ad fraud detected', [
                    'interaction_id' => $interaction->id,
                    'banner_id' => $banner->id,
                    'event_type' => $eventType,
                    'fraud_score' => $fraudScore,
                    'ip_address' => $data['ip_address'] ?? 'unknown',
                    'correlation_id' => $correlationId,
                ]);
            }

            Log::info('Ad interaction logged', [
                'interaction_id' => $interaction->id,
                'event_type' => $eventType,
                'is_fraud_suspected' => $isFraudSuspected,
                'correlation_id' => $correlationId,
            ]);

            return $interaction;
        } catch (\Throwable $e) {
            Log::error('Failed to log ad interaction', [
                'banner_id' => $banner->id,
                'event_type' => $eventType,
                'error' => $e->getMessage(),
                'error_type' => get_class($e),
            ]);

            \Sentry\captureException($e);
            throw $e;
        }
    }

    /**
     * Расчет fraud-score на основе поведения и паттернов (Production 2026).
     * 
     * @param array $data
     * @return int Score 0-100
     */
    private static function calculateFraudScore(array $data): int
    {
        $score = 0;

        // Проверка IP-адреса на подозрительность через БД датацентров/VPN провайдеров
        if (!empty($data['ip_address'])) {
            $ip = $data['ip_address'];
            
            // Проверить в БД датацентров (AWS, GCP, Azure, DigitalOcean и т.д.)
            $datacenter = DB::table('ip_datacenters')
                ->whereRaw('INET_ATON(?) BETWEEN start_ip AND end_ip', [$ip])
                ->first();
            
            if ($datacenter) {
                $score += 20; // Датацентр - подозрительно (боты часто используют облачные IP)
            }
            
            // Проверить в БД VPN провайдеров (ExpressVPN, NordVPN, Surfshark и т.д.)
            $vpn = DB::table('ip_vpn_providers')
                ->whereRaw('INET_ATON(?) BETWEEN start_ip AND end_ip', [$ip])
                ->first();
            
            if ($vpn) {
                $score += 25; // VPN - высокий риск мошенничества
            }
        }

        // Проверка user-agent на bot-подобность
        if (!empty($data['user_agent'])) {
            if (preg_match('/bot|crawler|spider|selenium|puppeteer/i', $data['user_agent'])) {
                $score += 30;  // Bot detected
            }
        }

        // Проверка координат клика (если на краю/невалидные координаты - подозрительно)
        if (isset($data['point_x'], $data['point_y'])) {
            // Нулевые координаты часто означают programmatic click
            if ($data['point_x'] === 0 && $data['point_y'] === 0) {
                $score += 15;
            }
            // Отрицательные координаты невозможны
            if ($data['point_x'] < 0 || $data['point_y'] < 0) {
                $score += 20;
            }
        }

        // Проверка на множественные быстрые клики проводится на уровне AdEngineService
        // через agile query к ad_interaction_logs с временными окнами

        return min($score, 100);
    }

    /**
     * Получить все подозрительные взаимодействия за период.
     * 
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function suspiciousInteractions()
    {
        return self::where('is_fraud_suspected', true)
            ->where('created_at', '>=', now()->subDays(7));
    }
}
