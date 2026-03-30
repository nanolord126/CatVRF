<?php declare(strict_types=1);

namespace App\Services\Webhook;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class WebhookSignatureValidator extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    // Dependencies injected via constructor
        // Add private readonly properties here
        public function validate(string $provider, string $payload, string $signature): bool
        {
            return match($provider) {
                'tinkoff' => $this->validateTinkoff($payload, $signature),
                'sber' => $this->validateSber($payload, $signature),
                'tochka' => $this->validateTochka($payload, $signature),
                'sbp' => $this->validateSbp($payload, $signature),
                default => false,
            };
        }

        private function validateTinkoff(string $payload, string $signature): bool
        {
            $secret = config('security.webhook_secrets.tinkoff');
            $expected = hash_hmac('sha256', $payload, $secret);
            return hash_equals($expected, $signature);
        }

        private function validateSber(string $payload, string $signature): bool
        {
            $secret = config('security.webhook_secrets.sber');
            $expected = hash_hmac('sha256', $payload, $secret);
            return hash_equals($expected, $signature);
        }

        private function validateTochka(string $payload, string $signature): bool
        {
            $secret = config('security.webhook_secrets.tochka');
            $expected = hash_hmac('sha256', $payload, $secret);
            return hash_equals($expected, $signature);
        }

        private function validateSbp(string $payload, string $signature): bool
        {
            $secret = config('security.webhook_secrets.sbp');
            $expected = hash_hmac('sha256', $payload, $secret);
            return hash_equals($expected, $signature);
        }
}
