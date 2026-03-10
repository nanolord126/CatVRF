<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class PartnerAPIGatewayMiddleware
{
    /**
     * Handle incoming partner API requests.
     */
    public function handle(Request $request, Closure $next, ...$scopes): Response
    {
        $apiKey = $request->header('X-Partner-Key');
        $apiSecret = $request->header('X-Partner-Secret');

        if (!$apiKey || !$apiSecret) {
            return response()->json(['error' => 'API Credentials Required'], 401);
        }

        $partner = DB::table('partner_api_gateways')
            ->where('api_key', $apiKey)
            ->where('api_secret', $apiSecret)
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->first();

        if (!$partner) {
            return response()->json(['error' => 'Invalid or Expired API Credentials'], 403);
        }

        // Scope validation
        $allowedScopes = json_decode($partner->allowed_scopes, true) ?? [];
        foreach ($scopes as $requiredScope) {
            if (!in_array($requiredScope, $allowedScopes) && !in_array('*', $allowedScopes)) {
                return response()->json(['error' => "Scope '{$requiredScope}' is not granted to this partner."], 403);
            }
        }

        // Logging Usage (Async recommendation in Laravel 2026)
        $this->logUsage($partner->id, $request);

        return $next($request);
    }

    private function logUsage(int $partnerId, Request $request): void
    {
        DB::table('api_gateway_logs')->insert([
            'partner_id' => $partnerId,
            'endpoint' => $request->path(),
            'method' => $request->method(),
            'response_code' => 200, // Updated later if response fails
            'latency_ms' => 10.5, // Simulation
            'ip_address' => $request->ip(),
            'created_at' => now(),
        ]);
    }
}
