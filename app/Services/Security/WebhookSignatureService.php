<?php
declare(strict_types=1);

namespace App\Services\Security;

use Illuminate\Support\Facades\Log;
use RuntimeException;

final class WebhookSignatureService
{
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
            'tinkoff' => $this->verifyTinkoff($payload, $signature),
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
        $secretKey = config('payment.webhooks.tinkoff.secret_key');
        
        if (!$secretKey) {
            Log::channel('fraud_alert')->warning('Tinkoff webhook secret not configured');
            return false;
        }
        
        $expectedSignature = hash_hmac('sha256', $payload, $secretKey);
        
        // Timing-safe comparison для защиты от timing attacks
        if (!hash_equals($expectedSignature, $signature)) {
            Log::channel('fraud_alert')->warning('Tinkoff webhook signature mismatch', [
                'provider' => 'tinkoff',
                'expected_first_chars' => substr($expectedSignature, 0, 8),
                'provided_first_chars' => substr($signature, 0, 8),
            ]);
            return false;
        }
        
        Log::channel('audit')->info('Tinkoff webhook verified', [
            'signature_hash' => substr($signature, 0, 8) . '***',
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
        $secretKey = config('payment.webhooks.sber.secret_key');
        $certificate = config('payment.webhooks.sber.certificate');
        
        // Если есть сертификат - проверить подпись сертификатом
        if ($certificate && file_exists($certificate)) {
            return $this->verifyWithCertificate($payload, $signature, $certificate);
        }
        
        // Иначе использовать HMAC-SHA256
        if (!$secretKey) {
            Log::channel('fraud_alert')->warning('Sber webhook secret not configured');
            return false;
        }
        
        $expectedSignature = hash_hmac('sha256', $payload, $secretKey);
        
        if (!hash_equals($expectedSignature, $signature)) {
            Log::channel('fraud_alert')->warning('Sber webhook signature mismatch', [
                'provider' => 'sber',
            ]);
            return false;
        }
        
        Log::channel('audit')->info('Sber webhook verified');
        
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
        $clientIp = request()->ip();
        $sbpIps = config('payment.webhooks.sbp.ip_whitelist', []);
        
        if (!$this->isIpWhitelisted($clientIp, $sbpIps)) {
            Log::channel('fraud_alert')->warning('SBP webhook IP not whitelisted', [
                'provider' => 'sbp',
                'client_ip' => $clientIp,
            ]);
            return false;
        }
        
        // Проверить HMAC
        $secretKey = config('payment.webhooks.sbp.secret_key');
        
        if (!$secretKey) {
            Log::channel('fraud_alert')->warning('SBP webhook secret not configured');
            return false;
        }
        
        $expectedSignature = hash_hmac('sha256', $payload, $secretKey);
        
        if (!hash_equals($expectedSignature, $signature)) {
            Log::channel('fraud_alert')->warning('SBP webhook signature mismatch', [
                'provider' => 'sbp',
            ]);
            return false;
        }
        
        Log::channel('audit')->info('SBP webhook verified', [
            'client_ip' => $clientIp,
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
                Log::channel('fraud_alert')->error('Failed to load certificate', [
                    'path' => $certificatePath,
                ]);
                return false;
            }
            
            $decodedSignature = base64_decode($signature, true);
            if ($decodedSignature === false) {
                Log::channel('fraud_alert')->warning('Invalid base64 signature');
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
                Log::channel('audit')->info('Certificate-based webhook verified');
                return true;
            }
            
            Log::channel('fraud_alert')->warning('Certificate-based webhook verification failed');
            return false;
            
        } catch (\Throwable $e) {
            Log::channel('fraud_alert')->error('Certificate verification error', [
                'error' => $e->getMessage(),
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
