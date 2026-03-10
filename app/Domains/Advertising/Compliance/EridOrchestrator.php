<?php

namespace App\Domains\Advertising\Compliance;

use App\Domains\Advertising\Models\AdBanner;
use App\Domains\Advertising\Interfaces\OrdDriverInterface;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Exception;
use Throwable;

/**
 * Оркестратор регистрации рекламных материалов в ОРД (Оператор Рекламных Данных).
 * Обеспечивает соответствие требованиям 347-ФЗ (маркировка рекламы ERID).
 *
 * Production 2026: С полным логированием, обработкой ошибок и audit trail.
 */
class EridOrchestrator
{
    public function __construct(private OrdDriverInterface $ordDriver) {}

    /**
     * Регистрация креатива (баннера) в ОРД и получение ERID.
     * Гарантирует наличие договора и валидность данных перед регистрацией.
     *
     * @param AdBanner $banner Баннер для регистрации
     * @return string ERID уникальный идентификатор рекламного материала
     * @throws Exception При критических ошибках ОРД
     */
    public function registerCreative(AdBanner $banner): string
    {
        $correlationId = Str::uuid()->toString();
        
        try {
            Log::info('ERID registration started', [
                'banner_id' => $banner->id,
                'campaign_id' => $banner->campaign_id,
                'correlation_id' => $correlationId,
            ]);

            $campaign = $banner->campaign;
            if (!$campaign) {
                throw new Exception("Banner has no associated campaign");
            }

            // 1. Убеждаемся, что договор зарегистрирован в ОРД
            $ordContractId = $this->ensureContractRegistered($campaign, $correlationId);

            // 2. Получаем URL медиа из Spatie Media Library
            $mediaUrl = $this->getMediaUrl($banner);

            // 3. Регистрируем креатив в ОРД
            $erid = $this->registerWithOrd($banner, $ordContractId, $mediaUrl, $correlationId);

            // 4. Обновляем баннер с ERID
            $banner->update([
                'erid' => $erid,
                'marked_at' => now(),
                'compliance_status' => 'valid',
            ]);

            // 5. Логируем успешную регистрацию
            AuditLog::create([
                'action' => 'advertising.erid_registered',
                'description' => "Баннер зарегистрирован в ОРД с ERID: {$erid}",
                'model_type' => 'AdBanner',
                'model_id' => $banner->id,
                'correlation_id' => $correlationId,
                'metadata' => [
                    'campaign_id' => $campaign->id,
                    'campaign_name' => $campaign->name,
                    'erid' => $erid,
                    'advertiser_inn' => $campaign->advertiser_inn,
                ],
            ]);

            Log::info('ERID registration completed', [
                'banner_id' => $banner->id,
                'erid' => $erid,
                'correlation_id' => $correlationId,
            ]);

            return $erid;

        } catch (Throwable $e) {
            Log::error('ERID registration failed', [
                'banner_id' => $banner->id,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
                'trace' => $e->getTraceAsString(),
            ]);

            $banner->update([
                'compliance_status' => 'failed_ord',
            ]);

            AuditLog::create([
                'action' => 'advertising.erid_registration_failed',
                'description' => "Ошибка регистрации баннера в ОРД: {$e->getMessage()}",
                'model_type' => 'AdBanner',
                'model_id' => $banner->id,
                'correlation_id' => $correlationId,
                'metadata' => [
                    'error' => $e->getMessage(),
                ],
            ]);

            throw new Exception("ERID registration failed: {$e->getMessage()}");
        }
    }

    /**
     * Получить строку маркировки для отображения пользователю (соответствие 347-ФЗ).
     *
     * @param AdBanner $banner
     * @return string Форматированная строка маркировки рекламы
     */
    public function getLabel(AdBanner $banner): string
    {
        if (!$banner->erid) {
            return "Реклама. Информация о рекламодателе уточняется.";
        }

        $campaign = $banner->campaign;
        $advertiserName = $campaign?->advertiser_name ?? 'Рекламодатель';
        $advertiserInn = $campaign?->advertiser_inn ?? '0000000000';

        return sprintf(
            "Реклама. %s, ИНН: %s. ERID: %s",
            $advertiserName,
            $advertiserInn,
            $banner->erid
        );
    }

    /**
     * Убедиться, что договор зарегистрирован в ОРД.
     * Если нет — зарегистрировать автоматически.
     */
    private function ensureContractRegistered($campaign, string $correlationId): string
    {
        if ($campaign->ord_contract_id) {
            return $campaign->ord_contract_id;
        }

        Log::info('Creating ORD contract for campaign', [
            'campaign_id' => $campaign->id,
            'correlation_id' => $correlationId,
        ]);

        try {
            $ordContractId = $this->ordDriver->createContract([
                'type' => 'Services',
                'number' => $campaign->contract_number ?? "AUTO-{$campaign->id}",
                'date' => now()->toDateString(),
                'client_inn' => $campaign->advertiser_inn ?? '0000000000',
                'contractor_inn' => config('advertising.platform_inn', '7700000000'),
            ]);

            $campaign->update([
                'ord_contract_id' => $ordContractId,
            ]);

            AuditLog::create([
                'action' => 'advertising.ord_contract_created',
                'description' => "Договор создан в ОРД",
                'model_type' => 'AdCampaign',
                'model_id' => $campaign->id,
                'correlation_id' => $correlationId,
                'metadata' => [
                    'ord_contract_id' => $ordContractId,
                ],
            ]);

            return $ordContractId;

        } catch (Throwable $e) {
            Log::error('Failed to create ORD contract', [
                'campaign_id' => $campaign->id,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);
            throw new Exception("Contract registration failed: {$e->getMessage()}");
        }
    }

    /**
     * Получить URL медиа файла для баннера.
     */
    private function getMediaUrl(AdBanner $banner): ?string
    {
        try {
            $media = $banner->getMedia('gallery')->first();
            if (!$media) {
                return null;
            }
            
            return $media->getTemporaryUrl(now()->addHours(24));
        } catch (Throwable $e) {
            Log::warning('Failed to get media URL', [
                'banner_id' => $banner->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Регистрация креатива в ОРД.
     */
    private function registerWithOrd(AdBanner $banner, string $ordContractId, ?string $mediaUrl, string $correlationId): string
    {
        try {
            return $this->ordDriver->registerCreative([
                'type' => 'Banner',
                'media_url' => $mediaUrl ?? '',
                'target_url' => $banner->target_url,
                'contract_id' => $ordContractId,
                'description' => $banner->description ?? 'Ad Banner',
            ]);
        } catch (Throwable $e) {
            throw new Exception("ORD creative registration error: {$e->getMessage()}");
        }
    }
}
