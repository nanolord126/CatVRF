declare(strict_types=1);

<?php
namespace Modules\Advertising\Services;
use Modules\Advertising\Models\Creative;
use Illuminate\Support\Facades\Http;
use Exception;

/**
 * OrdService
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class OrdService {
    // Dependencies injected via constructor
    // Add private readonly properties here
    public function getErid(Creative $creative): ?string {
        $driver = config('advertising.ord.driver');
        return match($driver) {
            'yandex' => $this->fetchYandexErid($creative),
            default => 'FALLBACK-' . uniqid()
        };
    }

    protected function fetchYandexErid(Creative $creative): string {
        try {
            $resp = Http::withToken(config('advertising.ord.api_key'))
                ->post('https://ord.yandex.ru/api/v1/creatives', [
                    'title' => $creative->title,
                    'text' => $creative->content,
                    'target_url' => $creative->link,
                ]);
            return $resp->json('erid') ?? 'ERROR-YANDEX';
        } catch (Exception $e) { return 'SYNC-PENDING'; }
    }
}
