<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\DTO\Security\BotDetectionResult;
use App\Enums\BotRiskLevel;
use App\Services\Security\BotDetectionService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * Bot Protection Middleware
 *
 * Applies bot detection and protection to critical routes.
 * Works with BotDetectionService to detect and block bot traffic.
 *
 * Protection levels:
 * - LOW: Allow with logging
 * - MEDIUM: Challenge (Turnstile) + rate limit
 * - HIGH: Block + cooldown
 * - CRITICAL: Block + extended cooldown + notifications
 *
 * @see https://github.com/nanolord126/CatVRF
 */
final readonly class BotProtectionMiddleware
{
    public function __construct(
        private readonly BotDetectionService $botDetection,
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @param  Closure  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!config('bot-protection.enabled', true)) {
            return $next($request);
        }

        // Skip for authenticated admin users (optional)
        if ($this->shouldSkipForAuthenticatedUser($request)) {
            return $next($request);
        }

        // Perform bot detection
        $result = $this->botDetection->detect(
            $request,
            $request->user()
        );

        // Apply protection measures
        if ($result->requiresProtection()) {
            return $this->applyProtection($request, $result, $next);
        }

        return $next($request);
    }

    /**
     * Check if middleware should skip for authenticated users
     *
     * @param  Request  $request
     * @return bool
     */
    private function shouldSkipForAuthenticatedUser(Request $request): bool
    {
        if (!config('bot-protection.skip_authenticated', false)) {
            return false;
        }

        $user = $request->user();
        if ($user === null) {
            return false;
        }

        // Skip for trusted users (optional implementation)
        // For now, skip all authenticated users if configured
        return true;
    }

    /**
     * Apply protection measures based on risk level
     *
     * @param  Request  $request
     * @param  BotDetectionResult  $result
     * @param  Closure  $next
     * @return Response
     */
    private function applyProtection(Request $request, BotDetectionResult $result, Closure $next): Response
    {
        // Apply protection in the service
        $this->botDetection->applyProtection($request, $result, $request->user());

        // Handle based on risk level
        return match ($result->riskLevel) {
            BotRiskLevel::MEDIUM => $this->handleMediumRisk($request, $result, $next),
            BotRiskLevel::HIGH => $this->handleHighRisk($request, $result),
            BotRiskLevel::CRITICAL => $this->handleCriticalRisk($request, $result),
            default => $next($request),
        };
    }

    /**
     * Handle Medium risk - Challenge + Rate Limit
     *
     * @param  Request  $request
     * @param  BotDetectionResult  $result
     * @param  Closure  $next
     * @return Response
     */
    private function handleMediumRisk(Request $request, BotDetectionResult $result, Closure $next): Response
    {
        // Check if already has valid Turnstile token
        $turnstileToken = $request->input('cf-turnstile-response');
        
        if ($turnstileToken && $this->validateTurnstile($turnstileToken, $request->ip())) {
            return $next($request);
        }

        // Return challenge response
        return response()->json([
            'error' => 'challenge_required',
            'message' => 'Please complete the security challenge',
            'risk_level' => $result->riskLevel->value,
            'turnstile_site_key' => config('bot-protection.turnstile.site_key'),
        ], 403);
    }

    /**
     * Handle High risk - Block
     *
     * @param  Request  $request
     * @param  BotDetectionResult  $result
     * @return Response
     */
    private function handleHighRisk(Request $request, BotDetectionResult $result): Response
    {
        return response()->json([
            'error' => 'access_denied',
            'message' => 'Your request has been blocked due to suspicious activity',
            'risk_level' => $result->riskLevel->value,
            'cooldown_hours' => config('bot-protection.protection.high.cooldown_hours', 24),
        ], 403);
    }

    /**
     * Handle Critical risk - Block with extended message
     *
     * @param  Request  $request
     * @param  BotDetectionResult  $result
     * @return Response
     */
    private function handleCriticalRisk(Request $request, BotDetectionResult $result): Response
    {
        return response()->json([
            'error' => 'permanently_denied',
            'message' => 'Your access has been permanently blocked due to severe security violations',
            'risk_level' => $result->riskLevel->value,
            'cooldown_hours' => config('bot-protection.protection.critical.cooldown_hours', 168),
        ], 403);
    }

    /**
     * Validate Turnstile token
     *
     * @param  string  $token
     * @param  string  $ip
     * @return bool
     */
    private function validateTurnstile(string $token, string $ip): bool
    {
        if (!config('bot-protection.turnstile.enabled', true)) {
            return true;
        }

        $secretKey = config('bot-protection.turnstile.secret_key');
        if ($secretKey === null) {
            return true; // Fail open if not configured
        }

        try {
            $response = \Illuminate\Support\Facades\Http::asForm()->post(
                config('bot-protection.turnstile.verify_url', 'https://challenges.cloudflare.com/turnstile/v0/siteverify'),
                [
                    'secret' => $secretKey,
                    'response' => $token,
                    'remoteip' => $ip,
                ]
            );

            $data = $response->json();

            return $data['success'] ?? false;
        } catch (\Throwable $e) {
            Log::channel('security')->warning('Turnstile validation failed', [
                'error' => $e->getMessage(),
            ]);

            // Fail open - don't block if validation fails
            return true;
        }
    }
}
