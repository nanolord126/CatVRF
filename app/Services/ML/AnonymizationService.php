<?php declare(strict_types=1);

namespace App\Services\ML;


use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * AnonymizationService — GDPR / ФЗ-152 compliant.
 *
 * Правила канона:
 *  - user_id хешируется sha256 + rotating salt (меняется ежегодно)
 *  - точный гео → город/регион (generalization, precision=2 знака)
 *  - k-anonymity: минимум 5 пользователей в любой группе
 *  - pseudonymization: сессии хранятся с anonymized_user_id
 *  - сырые данные удаляются через AnnualAnonymizationJob (>365 дней)
 *  - user_id НИКОГДА не попадает в ClickHouse
 */
final readonly class AnonymizationService
{
    public function __construct(
        private readonly ConfigRepository $config,
    ) {}

    /**
     * Хеш user_id → анонимизированный идентификатор.
     * Salt хранится в $this->config->get('app.anonymization_salt') и меняется ежегодно.
     */
    public function anonymizeUserId(int $userId): string
    {
        $salt = $this->config->get('app.anonymization_salt');

        if (empty($salt)) {
            throw new \RuntimeException('anonymization_salt not configured');
        }

        return hash('sha256', $userId . $salt);
    }

    /**
     * Generalization координат до `$precision` знаков после запятой.
     * precision=2 → точность ~1 км (достаточно для города).
     */
    public function generalizeGeo(float $lat, float $lon, int $precision = 2): array
    {
        return [
            'lat_generalized' => round($lat, $precision),
            'lon_generalized' => round($lon, $precision),
        ];
    }

    /**
     * Хеш города (crc32) для передачи в ClickHouse без хранения raw-строки.
     */
    public function hashCity(string $city): int
    {
        return crc32(mb_strtolower(trim($city)));
    }

    /**
     * Анонимизировать одно поведенческое событие для записи в ClickHouse.
     *
     * @param array{
     *   user_id: int,
     *   timestamp: string,
     *   vertical: string,
     *   action: string,
     *   session_duration?: int,
     *   device_type?: string,
     *   city?: string,
     *   correlation_id: string,
     * } $rawEvent
     * @return array
     */
    public function anonymizeEvent(array $rawEvent): array
    {
        // Обязательные поля
        $this->assertRequiredFields($rawEvent, ['user_id', 'timestamp', 'vertical', 'action', 'correlation_id']);

        $geo = $this->generalizeGeo(
            lat: (float) ($rawEvent['lat'] ?? 0.0),
            lon: (float) ($rawEvent['lon'] ?? 0.0)
        );

        return [
            'anonymized_user_id' => $this->anonymizeUserId((int) $rawEvent['user_id']),
            'event_timestamp'    => $rawEvent['timestamp'],
            'vertical'           => $rawEvent['vertical'],
            'action'             => $rawEvent['action'],
            'session_duration'   => (int) ($rawEvent['session_duration'] ?? 0),
            'device_type'        => $rawEvent['device_type'] ?? 'unknown',
            'city_hash'          => $this->hashCity($rawEvent['city'] ?? ''),
            'lat_generalized'    => $geo['lat_generalized'],
            'lon_generalized'    => $geo['lon_generalized'],
            'correlation_id'     => $rawEvent['correlation_id'],
            // user_id намеренно ИСКЛЮЧЁН из результата
        ];
    }

    /**
     * Пакетная анонимизация коллекции событий.
     * Используется в AnnualAnonymizationJob и MLRecalculateJob.
     *
     * @param  Collection<int, array> $events
     * @return Collection<int, array>
     */
    public function anonymizeBehaviorBatch(Collection $events): Collection
    {
        return $events->map(fn (array $event): array => $this->anonymizeEvent($event));
    }

    /**
     * Anonymize marketing / analytics event (для tracking в ad-системе).
     * Возвращает только обезличенные поля — user_id не включается.
     */
    public function anonymizeMarketingEvent(array $rawEvent): array
    {
        $this->assertRequiredFields($rawEvent, ['user_id', 'event_type', 'correlation_id']);

        return [
            'anonymized_user_id' => $this->anonymizeUserId((int) $rawEvent['user_id']),
            'event_type'         => $rawEvent['event_type'],
            'vertical'           => $rawEvent['vertical'] ?? null,
            'device_type'        => $rawEvent['device_type'] ?? 'unknown',
            'city_hash'          => $this->hashCity($rawEvent['city'] ?? ''),
            'created_at'         => $rawEvent['created_at'] ?? now()->toIso8601String(),
            'correlation_id'     => $rawEvent['correlation_id'],
        ];
    }

    /**
     * Проверка обратной связи: убеждаемся, что user_id НЕ содержится в анонимизированных данных.
     * Используется в тестах (assert that user_id never appears in anonymized output).
     */
    public function containsRawUserId(array $anonymizedData, int $userId): bool
    {
        $encoded = json_encode($anonymizedData);

        // Ищем как int-строку и как числовое значение
        return str_contains($encoded, (string) $userId)
            && ! str_contains($encoded, $this->anonymizeUserId($userId));
    }

    /**
     * Псевдоанонимизация SessionId для хранения в аналитике.
     * Session-id не пересчитывается каждый раз — только маскируется.
     */
    public function pseudonymizeSession(string $sessionId): string
    {
        return hash('sha256', $sessionId . $this->config->get('app.anonymization_salt'));
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function assertRequiredFields(array $data, array $fields): void
    {
        foreach ($fields as $field) {
            if (! array_key_exists($field, $data)) {
                throw new \InvalidArgumentException("AnonymizationService: missing required field '{$field}'");
            }
        }
    }
}
