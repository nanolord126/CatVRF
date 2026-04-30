<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\ApiKey;
use App\Services\Security\ApiKeyService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Log\LogManager;
use Illuminate\Contracts\Routing\ResponseFactory;
use Symfony\Component\HttpFoundation\Response;

final readonly class ApiKeyAuthentication
{
    public function __construct(
        private readonly LogManager $logger,
        private readonly ResponseFactory $response,
        private readonly ApiKeyService $apiKeyService,
    ) {}

    /**
     * Валидация API ключа для B2B интеграций.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $ability  Required ability (optional)
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, ?string $ability = null): Response
    {
        $apiKey = $this->extractApiKey($request);

        if (!$apiKey) {
            $this->logger->channel('security')->warning('API request without API key', [
                'ip' => $request->ip(),
                'path' => $request->path(),
                'correlation_id' => $request->header('X-Correlation-ID'),
            ]);

            return $this->response->json([
                'error' => 'API key required',
                'message' => 'Please provide a valid API key via X-API-Key or Authorization header',
                'correlation_id' => $request->header('X-Correlation-ID'),
            ], 401);
        }

        $apiKeyModel = $this->apiKeyService->validate($apiKey);

        if (!$apiKeyModel) {
            $this->logger->channel('fraud_alert')->warning('Invalid API key attempt', [
                'ip' => $request->ip(),
                'path' => $request->path(),
                'key_preview' => substr($apiKey, -8),
                'correlation_id' => $request->header('X-Correlation-ID'),
            ]);

            return $this->response->json([
                'error' => 'Invalid API key',
                'message' => 'The provided API key is invalid, expired, or revoked',
                'correlation_id' => $request->header('X-Correlation-ID'),
            ], 401);
        }

        // Check ability if required
        if ($ability !== null && !$this->apiKeyService->hasAbility($apiKeyModel, $ability)) {
            $this->logger->channel('security')->warning('API key lacks required ability', [
                'ip' => $request->ip(),
                'path' => $request->path(),
                'required_ability' => $ability,
                'key_preview' => $apiKeyModel->key_preview,
                'correlation_id' => $request->header('X-Correlation-ID'),
            ]);

            return $this->response->json([
                'error' => 'Insufficient permissions',
                'message' => 'API key does not have the required ability: ' . $ability,
                'correlation_id' => $request->header('X-Correlation-ID'),
            ], 403);
        }

        // Store in request for controllers
        $request->attributes->set('api_key', $apiKeyModel);
        $request->merge([
            'tenant' => $apiKeyModel->tenant,
            'user' => $apiKeyModel->user,
            'api_abilities' => $apiKeyModel->abilities ?? [],
        ]);

        $this->logger->channel('audit')->info('API key authenticated', [
            'key_id' => $apiKeyModel->id,
            'tenant_id' => $apiKeyModel->tenant_id,
            'key_preview' => $apiKeyModel->key_preview,
            'correlation_id' => $request->header('X-Correlation-ID'),
        ]);

        return $next($request);
    }

    /**
     * Extract API key from request headers
     */
    private function extractApiKey(Request $request): ?string
    {
        // Try X-API-Key header first
        $apiKey = $request->header('X-API-Key');
        if ($apiKey) {
            return $apiKey;
        }

        // Try Authorization header with Bearer token
        $authorization = $request->header('Authorization');
        if ($authorization && str_starts_with($authorization, 'Bearer ')) {
            return substr($authorization, 7);
        }

        return null;
    }
}
