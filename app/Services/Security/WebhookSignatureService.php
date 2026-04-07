<?php declare(strict_types=1);

namespace App\Services\Security;




use Illuminate\Http\Request;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Support\Str;
use RuntimeException;
use Illuminate\Log\LogManager;

final readonly class WebhookSignatureService
{
    public function __construct(
        private readonly Request $request,
        private readonly ConfigRepository $config,
        private readonly LogManager $logger,
    ) {}


    /**
         * Проверить подпись webhook от платёжного шлюза.
         *
         * @param string $provider Название провайдера (tinkoff, sber, sbp)
         * @param string $payload Сырой payload webhook
         * @param string $signature Подпись из заголовка
         * @return bool
         * @throws RuntimeException Если провайдер не поддерживается
         */
        public function verify(string $provider, string $payload, string $signature): bool
        {
            return match(strtolower($provider)) {
                'sber' => $this->verifySber($payload, $signature),
                'sbp' => $this->verifySbp($payload, $signature),
                default => throw new RuntimeException("Unknown webhook provider: {$provider}"),
            };
        }

        /**
         * Проверить Tinkoff webhook подпись.
         *
         * Tinkoff использует HMAC-SHA256 с secret key.
         * Формат: hmac_sha256(payload, secret_key)
         *
         * @param string $payload
         * @param string $signature
         * @return bool
         */
        private function verifyTinkoff(string $payload, string $signature): bool
        {
            $secretKey = $this->config->get('payment.webhooks.tinkoff.secret_key');

            if (!$secretKey) {
                $this->logger->channel('fraud_alert')->warning('Tinkoff webhook secret not configured');
                return false;
            }

            $expectedSignature = hash_hmac('sha256', $payload, $secretKey);

            // Timing-safe comparison для защиты от timing attacks
            if (!hash_equals($expectedSignature, $signature)) {
                $this->logger->channel('fraud_alert')->warning('Tinkoff webhook signature mismatch', [
                    'provider' => 'tinkoff',
                    'expected_first_chars' => substr($expectedSignature, 0, 8),
                    'provided_first_chars' => substr($signature, 0, 8),
                'correlation_id' => $this->request->header('X-Correlation-ID', $this->correlationId ?? ''),
            ]);
                return false;
            }

            $this->logger->channel('audit')->info('Tinkoff webhook verified', [
                'signature_hash' => substr($signature, 0, 8) . '***',
                'correlation_id' => $this->request->header('X-Correlation-ID', $this->correlationId ?? ''),
            ]);

            return true;
        }

        /**
         * Проверить Sber webhook подпись.
         *
         * Sber может использовать сертификат или HMAC-SHA256.
         * Формат: обычно X-Signature заголовок с HMAC
         *
         * @param string $payload
         * @param string $signature
         * @return bool
         */
        private function verifySber(string $payload, string $signature): bool
        {
            $secretKey = $this->config->get('payment.webhooks.sber.secret_key');
            $certificate = $this->config->get('payment.webhooks.sber.certificate');

            // Если есть сертификат - проверить подпись сертификатом
            if ($certificate && file_exists($certificate)) {
                return $this->verifyWithCertificate($payload, $signature, $certificate);
            }

            // Иначе использовать HMAC-SHA256
            if (!$secretKey) {
                $this->logger->channel('fraud_alert')->warning('Sber webhook secret not configured');
                return false;
            }

            $expectedSignature = hash_hmac('sha256', $payload, $secretKey);

            if (!hash_equals($expectedSignature, $signature)) {
                $this->logger->channel('fraud_alert')->warning('Sber webhook signature mismatch', [
                    'provider' => 'sber',
                'correlation_id' => $this->request->header('X-Correlation-ID', $this->correlationId ?? ''),
            ]);
                return false;
            }

            $this->logger->channel('audit')->info('Sber webhook verified');

            return true;
        }

        /**
         * Проверить СБП webhook подпись.
         *
         * СБП обычно использует IP whitelist + HMAC.
         * Проверяем оба условия.
         *
         * @param string $payload
         * @param string $signature
         * @return bool
         */
        private function verifySbp(string $payload, string $signature): bool
        {
            // Проверить IP whitelist
            $clientIp = $this->request->ip();
            $sbpIps = $this->config->get('payment.webhooks.sbp.ip_whitelist', []);

            if (!$this->isIpWhitelisted($clientIp, $sbpIps)) {
                $this->logger->channel('fraud_alert')->warning('SBP webhook IP not whitelisted', [
                    'provider' => 'sbp',
                    'client_ip' => $clientIp,
                'correlation_id' => $this->request->header('X-Correlation-ID', $this->correlationId ?? ''),
            ]);
                return false;
            }

            // Проверить HMAC
            $secretKey = $this->config->get('payment.webhooks.sbp.secret_key');

            if (!$secretKey) {
                $this->logger->channel('fraud_alert')->warning('SBP webhook secret not configured');
                return false;
            }

            $expectedSignature = hash_hmac('sha256', $payload, $secretKey);

            if (!hash_equals($expectedSignature, $signature)) {
                $this->logger->channel('fraud_alert')->warning('SBP webhook signature mismatch', [
                    'provider' => 'sbp',
                'correlation_id' => $this->request->header('X-Correlation-ID', $this->correlationId ?? ''),
            ]);
                return false;
            }

            $this->logger->channel('audit')->info('SBP webhook verified', [
                'client_ip' => $clientIp,
                'correlation_id' => $this->request->header('X-Correlation-ID', $this->correlationId ?? ''),
            ]);

            return true;
        }

        /**
         * Проверить подпись с использованием сертификата (OpenSSL).
         *
         * @param string $payload
         * @param string $signature (base64 encoded)
         * @param string $certificatePath
         * @return bool
         */
        private function verifyWithCertificate(string $payload, string $signature, string $certificatePath): bool
        {
            try {
                $publicKey = openssl_pkey_get_public(file_get_contents($certificatePath));

                if (!$publicKey) {
                    $this->logger->channel('fraud_alert')->error('Failed to load certificate', [
                        'path' => $certificatePath,
                'correlation_id' => $this->request->header('X-Correlation-ID', $this->correlationId ?? ''),
            ]);
                    return false;
                }

                $decodedSignature = base64_decode($signature, true);
                if ($decodedSignature === false) {
                    $this->logger->channel('fraud_alert')->warning('Invalid base64 signature');
                    return false;
                }

                $verified = openssl_verify(
                    $payload,
                    $decodedSignature,
                    $publicKey,
                    OPENSSL_ALGO_SHA256
                );

                openssl_pkey_free($publicKey);

                if ($verified === 1) {
                    $this->logger->channel('audit')->info('Certificate-based webhook verified');
                    return true;
                }

                $this->logger->channel('fraud_alert')->warning('Certificate-based webhook verification failed');
                return false;

            } catch (\Throwable $e) {
                $this->logger->channel('fraud_alert')->error('Certificate verification error', [
                    'error' => $e->getMessage(),
                'correlation_id' => $this->request->header('X-Correlation-ID', $this->correlationId ?? ''),
            ]);
                return false;
            }
        }

        /**
         * Проверить, находится ли IP в whitelist.
         *
         * Поддерживает CIDR notation (10.0.0.0/8)
         *
         * @param string $ip
         * @param array $whitelist
         * @return bool
         */
        private function isIpWhitelisted(string $ip, array $whitelist): bool
        {
            if (empty($whitelist)) {
                return false;
            }

            foreach ($whitelist as $allowed) {
                if ($this->ipInCidr($ip, $allowed)) {
                    return true;
                }
            }

            return false;
        }

        /**
         * Проверить, находится ли IP в CIDR range.
         *
         * @param string $ip IPv4 адрес
         * @param string $cidr CIDR notation (10.0.0.0/8 или 10.0.0.0)
         * @return bool
         */
        private function ipInCidr(string $ip, string $cidr): bool
        {
            if (strpos($cidr, '/') === false) {
                // Простое сравнение без CIDR
                return $ip === $cidr;
            }

            [$subnet, $bits] = explode('/', $cidr);
            $ip = ip2long($ip);
            $subnet = ip2long($subnet);
            $mask = -1 << (32 - (int)$bits);
            $subnet &= $mask;

            return ($ip & $mask) === $subnet;
        }
}
