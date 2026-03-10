<?php

namespace App\Http\Middleware;

use App\Services\Common\Security\AIAnomalyDetector;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Stancl\Tenancy\Facades\Tenancy;

class FraudControlMiddleware
{
    protected AIAnomalyDetector $detector;

    public function __construct(AIAnomalyDetector $detector)
    {
        $this->detector = $detector;
    }

    /**
     * Middleware для динамического контроля фрода в реальном времени.
     * Проверяет Risk Score пользователя перед выполнением мутации.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Не проверяем GET запросы, только мутирующие (POST, PUT, DELETE, PATCH)
        if ($request->isMethod('GET')) {
            return $next($request);
        }

        $tenant = (Tenancy::initialized()) ? Tenancy::tenant() : null;
        if (!$tenant) {
            return $next($request);
        }

        $userId = auth()->id();
        $action = $request->route()?->getName() ?? $request->path();
        
        $context = [
            'ip' => $request->ip(),
            'ua' => $request->header('User-Agent'),
            'amount' => $request->input('amount'),
            'payload' => $request->except(['password', 'card_number']),
        ];

        $riskScore = $this->detector->analyze($tenant, $userId, $action, $context);

        // Порог авто-блокировки (80+) или запрос расширенной верификации (50+)
        if ($riskScore >= 80) {
            abort(403, "Action blocked by AI Fraud Control (Risk: {$riskScore}). Contact administrator.");
        }

        // В реальном проекте здесь можно перенаправить на страницу 2FAOTP
        // if ($riskScore >= 50 && !$request->has('2fa_token')) { ... }

        return $next($request);
    }
}
