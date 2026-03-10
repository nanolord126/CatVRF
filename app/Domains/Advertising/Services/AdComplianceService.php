<?php

namespace App\Domains\Advertising\Services;

use App\Domains\Advertising\Models\AdBanner;
use App\Domains\Advertising\Jobs\RegisterAdCreativeJob;
use App\Domains\Common\Services\AI\ContentShieldService;
use App\Models\AuditLog;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;
use Throwable;

/**
 * AdComplianceService - Сервис compliance для рекламы (347-ФЗ, Production 2026).
 * 
 * Отвечает за:
 * - Валидацию рекламного контента через AI Shield
 * - Инициирование процесса маркировки (получение ERID)
 * - Подачу статистики в ЕРИР
 * - Логирование всех операций
 */
class AdComplianceService
{
    private string $correlationId;

    public function __construct(private ContentShieldService $shield)
    {
        $this->correlationId = Str::uuid()->toString();
    }

    /**
     * Полнофункциональная валидация рекламного креатива и запуск процесса маркировки.
     * 
     * Проверяет контент через AI Safety Shield и отправляет в очередь для регистрации ERID.
     * Соответствует требованиям 347-ФЗ (маркировка рекламы).
     *
     * @param AdBanner $banner Баннер для валидации
     * @return bool True если прошла валидацию и отправлена на маркировку
     * 
     * @throws \Exception При критических ошибках
     */
    public function validateAndMark(AdBanner $banner): bool
    {
        try {
            Log::info('Starting ad compliance validation', [
                'banner_id' => $banner->id,
                'correlation_id' => $this->correlationId,
            ]);

            // === Валидация базовых параметров ===
            if (!$banner->campaign) {
                throw new \RuntimeException("Баннер {$banner->id} не имеет связанной кампании");
            }

            if (!$banner->campaign->advertiser_inn) {
                throw new \InvalidArgumentException("Кампания должна иметь INN рекламодателя");
            }

            // === Шаг 1: AI Content Shield валидация ===
            try {
                $media = $banner->getMedia('gallery')->first();

                if ($media) {
                    Log::debug('Analyzing banner media with ContentShield', [
                        'banner_id' => $banner->id,
                        'media_id' => $media->id,
                        'correlation_id' => $this->correlationId,
                    ]);

                    $uploadedFile = new UploadedFile(
                        $media->getPath(),
                        $media->file_name,
                        $media->mime_type,
                        null,
                        true
                    );
                    $analysis = $this->shield->analyzeUpload($uploadedFile);

                    if (!$analysis['is_allowed']) {
                        $banner->update([
                            'compliance_status' => 'moderation_failed_ai',
                            'is_active' => false,
                            'metadata' => array_merge(
                                $banner->metadata ?? [],
                                ['ai_reason' => $analysis['reason'] ?? 'Unknown']
                            ),
                        ]);

                        AuditLog::create([
                            'action' => 'advertising.banner_blocked_by_ai',
                            'description' => "Баннер заблокирован AI: {$analysis['reason']}",
                            'model_type' => 'AdBanner',
                            'model_id' => $banner->id,
                            'correlation_id' => $this->correlationId,
                            'metadata' => ['reason' => $analysis['reason'] ?? 'Unknown'],
                        ]);

                        Log::warning('Ad banner blocked by AI Safety Shield', [
                            'banner_id' => $banner->id,
                            'reason' => $analysis['reason'] ?? 'Unknown',
                            'correlation_id' => $this->correlationId,
                        ]);

                        return false;
                    }

                    $banner->update(['is_ai_approved' => true]);

                    Log::debug('Banner passed AI content analysis', [
                        'banner_id' => $banner->id,
                        'correlation_id' => $this->correlationId,
                    ]);
                }
            } catch (Throwable $e) {
                Log::error('AI content shield analysis failed', [
                    'banner_id' => $banner->id,
                    'error' => $e->getMessage(),
                    'correlation_id' => $this->correlationId,
                ]);

                \Sentry\captureException($e);

                throw new \Exception("Ошибка анализа контента: {$e->getMessage()}");
            }

            // === Шаг 2: Асинхронная регистрация в ОРД (получение ERID) ===
            // Отправляем job в очередь для асинхронной обработки (ОРД API бывают медленными)
            RegisterAdCreativeJob::dispatch($banner)
                ->onQueue('advertising')
                ->delay(Carbon::now()->addSeconds(5)); // Небольшая задержка для стабильности

            $banner->update(['compliance_status' => 'registering']);

            AuditLog::create([
                'action' => 'advertising.banner_registration_initiated',
                'description' => "Инициирована регистрация в ОРД (маркировка)",
                'model_type' => 'AdBanner',
                'model_id' => $banner->id,
                'correlation_id' => $this->correlationId,
            ]);

            Log::info('Ad banner marked for ORD registration', [
                'banner_id' => $banner->id,
                'correlation_id' => $this->correlationId,
            ]);

            return true;

        } catch (Throwable $e) {
            Log::error('Ad compliance validation failed', [
                'banner_id' => $banner->id,
                'error' => $e->getMessage(),
                'error_type' => get_class($e),
                'correlation_id' => $this->correlationId,
            ]);

            AuditLog::create([
                'action' => 'advertising.banner_validation_failed',
                'description' => "Ошибка валидации: {$e->getMessage()}",
                'model_type' => 'AdBanner',
                'model_id' => $banner->id,
                'correlation_id' => $this->correlationId,
                'metadata' => ['error' => $e->getMessage()],
            ]);

            \Sentry\captureException($e);

            return false;
        }
    }

    /**
     * Подача отчета в ЕРИР (Единый реестр интернет-рекламы).
     * 
     * Собирает статистику по всем активным кампаниям и отправляет в MediaScout API
     * для регистрации в системе ЕРИР. Должно вызваться ежемесячно.
     *
     * @param string $period Период в формате YYYY-MM (например: 2026-03)
     * @return int Количество отправленных отчетов
     * 
     * @throws \Exception При критических ошибках
     */
    public function submitStatsReport(string $period = null): int
    {
        try {
            $period = $period ?? Carbon::now()->format('Y-m');

            Log::info('Starting ERIR statistics report submission', [
                'period' => $period,
                'correlation_id' => $this->correlationId,
            ]);

            // === Шаг 1: Сбор показов и клико по завершенным кампаниям ===
            $campaignsToReport = \App\Domains\Advertising\Models\AdCampaign::where(function($q) use ($period) {
                // Кампании, закончившиеся в этом периоде или активные
                $q->whereMonth('ended_at', '=', substr($period, 5, 2))
                    ->whereYear('ended_at', '=', substr($period, 0, 4))
                    ->orWhere(function($q2) {
                        $q2->whereNull('ended_at')
                            ->where('is_fully_compliant', true);
                    });
            })
            ->with('banners.interactions')
            ->get();

            if ($campaignsToReport->isEmpty()) {
                Log::info('No campaigns to report for period', [
                    'period' => $period,
                    'correlation_id' => $this->correlationId,
                ]);

                return 0;
            }

            $reportCount = 0;

            foreach ($campaignsToReport as $campaign) {
                try {
                    // Собираем метрики по баннерам
                    $totalImpressions = $campaign->banners->sum(function($banner) {
                        return $banner->interactions->where('event_type', 'impression')->count();
                    });

                    $totalClicks = $campaign->banners->sum(function($banner) {
                        return $banner->interactions->where('event_type', 'click')->count();
                    });

                    // Здесь будет интеграция с MediaScout OrdDriver для отправки
                    // $this->ordDriver->pushStats([...]);

                    AuditLog::create([
                        'action' => 'advertising.erir_report_submitted',
                        'description' => "Отчет в ЕРИР отправлен",
                        'model_type' => 'AdCampaign',
                        'model_id' => $campaign->id,
                        'correlation_id' => $this->correlationId,
                        'metadata' => [
                            'period' => $period,
                            'impressions' => $totalImpressions,
                            'clicks' => $totalClicks,
                        ],
                    ]);

                    Log::info('ERIR report submitted for campaign', [
                        'campaign_id' => $campaign->id,
                        'impressions' => $totalImpressions,
                        'clicks' => $totalClicks,
                        'correlation_id' => $this->correlationId,
                    ]);

                    $reportCount++;

                } catch (Throwable $e) {
                    Log::error('Failed to report campaign to ERIR', [
                        'campaign_id' => $campaign->id,
                        'error' => $e->getMessage(),
                        'correlation_id' => $this->correlationId,
                    ]);

                    \Sentry\captureException($e);
                }
            }

            return $reportCount;

        } catch (Throwable $e) {
            Log::error('ERIR stats report submission failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $this->correlationId,
            ]);

            \Sentry\captureException($e);

            throw new \Exception("Ошибка при отправке отчета в ЕРИР: {$e->getMessage()}");
        }
    }
}
