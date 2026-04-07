<?php declare(strict_types=1);

namespace App\Services\Webhook;


use Illuminate\Contracts\Config\Repository as ConfigRepository;
/**
 * Class WebhookSignatureValidator
 *
 * Component of the CatVRF platform.
 * Follows strict coding standards:
 * - final class (no inheritance unless required)
 * - private readonly properties
 * - Constructor injection only
 * - correlation_id in all operations
 *
 * @package App\Services\Webhook
 */
final readonly class WebhookSignatureValidator
{
    public function __construct(
        private readonly ConfigRepository $config,
    ) {}

    // Dependencies injected via constructor
        // Add private readonly properties here
        public function validate(string $provider, string $payload, string $signature): bool
        {
            return match($provider) {
                'sber' => $this->validateSber($payload, $signature),
                'tochka' => $this->validateTochka($payload, $signature),
                'sbp' => $this->validateSbp($payload, $signature),
                default => false,
            };
        }

        private function validateTinkoff(string $payload, string $signature): bool
        {
            $secret = $this->config->get('security.webhook_secrets.tinkoff');
            $expected = hash_hmac('sha256', $payload, $secret);
            return hash_equals($expected, $signature);
        }

        private function validateSber(string $payload, string $signature): bool
        {
            $secret = $this->config->get('security.webhook_secrets.sber');
            $expected = hash_hmac('sha256', $payload, $secret);
            return hash_equals($expected, $signature);
        }

        private function validateTochka(string $payload, string $signature): bool
        {
            $secret = $this->config->get('security.webhook_secrets.tochka');
            $expected = hash_hmac('sha256', $payload, $secret);
            return hash_equals($expected, $signature);
        }

        private function validateSbp(string $payload, string $signature): bool
        {
            $secret = $this->config->get('security.webhook_secrets.sbp');
            $expected = hash_hmac('sha256', $payload, $secret);
            return hash_equals($expected, $signature);
        }
}
