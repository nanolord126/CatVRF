<?php

declare(strict_types=1);

namespace Modules\Contraindications\Application\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Contraindications\Domain\ValueObjects\Scope;
use Symfony\Component\HttpFoundation\Response;

final class ContraindicationPrivacyMiddleware
{
    /**
     * Handle an incoming request.
     *
     * Automatically filters allergies/contraindications based on the current scope
     */
    public function handle(Request $request, Closure $next, string $scope): Response
    {
        $scopeEnum = Scope::tryFrom($scope);

        if (!$scopeEnum) {
            return $next($request);
        }

        // Store the current scope in the request for later use
        $request->attributes->set('contraindication_scope', $scopeEnum);

        return $next($request);
    }
}
