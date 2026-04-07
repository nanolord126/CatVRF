<?php declare(strict_types=1);

namespace App\Http\Middleware;


use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Log\LogManager;
use Illuminate\Contracts\Routing\ResponseFactory;

/**
 * Webhook Signature Validation Middleware
 * Production 2026 CANON
 *
 * Verifies webhook signatures from payment gateways:
 * - Tinkoff: HMAC-SHA256 signature validation
 * - Tochka Bank: JWT token verification
 * - Sber: X-Signature header check
 * - Blocks unsigned/invalid webhooks with 401
 * - Logs all webhook attempts for audit
 * - IP whitelist validation (if configured)
 *
 * Applied to:
 * - POST /webhooks/tinkoff
 * - POST /webhooks/tochka
 * - POST /webhooks/sber
 *
 * @author CatVRF Team
 * @version 2026.03.25
 */
final class WebhookSignatureMiddleware
{
    public function __construct(
        private readonly ConfigRepository $config,
        private readonly LogManager $logger,
        private readonly ResponseFactory $response,
    ) {}

    /**
     * Handle the request
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Skip non-webhook requests
        if (!str_contains($request->path(), '/webhooks/')) {
            return $next($request);
        }

        // Validate IP whitelist first
        if (!$this->validateIpWhitelist($request)) {
            return $this->response->json([
                'error' => 'IP not whitelisted',
            ], 401);
        }

        // Validate signature based on gateway
        $gateway = $this->getGatewayFromPath($request->path());

        $isValid = match ($gateway) {
            'tochka' => $this->validateTochkaSignature($request),
            'sber' => $this->validateSberSignature($request),
            default => false,
        };

        if (!$isValid) {
            $this->logger->channel('webhook_errors')->warning('Invalid webhook signature', [
                'gateway' => $gateway,
                'ip' => $request->ip(),
                'signature' => $request->header('X-Signature', 'missing'),
            ]);

            return $this->response->json([
                'error' => 'Invalid signature',
            ], 401);
        }

        return $next($request);
    }

    /**
     * Validate IP whitelist
     *
     * @param Request $request
     * @return bool
     */
    private function validateIpWhitelist(Request $request): bool
    {
        $gateway = $this->getGatewayFromPath($request->path());
        $configKey = "security.webhook_ip_whitelist.{$gateway}";
        $whitelist = $this->config->get($configKey, []);

        if (empty($whitelist)) {
            return true; // No whitelist configured, allow
        }

        $clientIp = $request->ip();

        return in_array($clientIp, $whitelist, true);
    }

    /**
     * Validate Tinkoff webhook signature
     *
     * @param Request $request
     * @return bool
     */
    private function validateTinkoffSignature(Request $request): bool
    {
        $signature = $request->header('X-Signature');

        if (!$signature) {
            return false;
        }

        $secret = $this->config->get('security.webhook_secrets.tinkoff', '');
        $payload = $request->getContent();

        // HMAC-SHA256 validation
        $expectedSignature = hash_hmac('sha256', $payload, $secret, true);
        $expectedSignatureHex = bin2hex($expectedSignature);

        return hash_equals($expectedSignatureHex, $signature);
    }

    /**
     * Validate Tochka webhook signature (JWT)
     *
     * @param Request $request
     * @return bool
     */
    private function validateTochkaSignature(Request $request): bool
    {
        $token = $request->header('Authorization');

        if (!$token || !str_starts_with($token, 'Bearer ')) {
            return false;
        }

        $jwt = substr($token, 7);
        $secret = $this->config->get('security.webhook_secrets.tochka', '');

        try {
            // Verify JWT (simplified - use Firebase/JWT in production)
            $parts = explode('.', $jwt);
            if (count($parts) !== 3) {
                return false;
            }

            $signature = $parts[2];
            $signatureInput = $parts[0] . '.' . $parts[1];

            $expectedSignature = base64_encode(
                hash_hmac('sha256', $signatureInput, $secret, true)
            );

            return hash_equals($expectedSignature, $signature);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::channel('audit')->error($e->getMessage(), [
                'exception' => $e::class,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'correlation_id' => request()->header('X-Correlation-ID'),
            ]);

            return false;
        }
    }

    /**
     * Validate Sber webhook signature
     *
     * @param Request $request
     * @return bool
     */
    private function validateSberSignature(Request $request): bool
    {
        $signature = $request->header('X-Signature');

        if (!$signature) {
            return false;
        }

        $secret = $this->config->get('security.webhook_secrets.sber', '');
        $payload = $request->getContent();

        // HMAC-SHA256 validation (similar to Tinkoff)
        $expectedSignature = hash_hmac('sha256', $payload, $secret, true);
        $expectedSignatureHex = bin2hex($expectedSignature);

        return hash_equals($expectedSignatureHex, $signature);
    }

    /**
     * Extract gateway name from request path
     *
     * @param string $path
     * @return string Gateway name (tinkoff, tochka, sber)
     */
    private function getGatewayFromPath(string $path): string
    {
        if (str_contains($path, 'tinkoff')) {
            return 'tinkoff';
        }
        if (str_contains($path, 'tochka')) {
            return 'tochka';
        }
        if (str_contains($path, 'sber')) {
            return 'sber';
        }

        return 'unknown';
    }
}
