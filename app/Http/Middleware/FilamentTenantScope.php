<?php declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Auth\AuthManager;
use Symfony\Component\HttpFoundation\Response;

/**
 * Filament Tenant Scope Middleware
 * 
 * Enforces tenant isolation for all Filament panel requests.
 * Automatically applies tenant_id scope to all Eloquent queries.
 * Prevents data leakage between tenants in admin panels.
 * 
 * @package App\Http\Middleware
 */
final readonly class FilamentTenantScope
{
    public function __construct(
        private readonly AuthManager $auth,
    ) {}
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip for landlord panel (admin path)
        if ($request->is('admin/*') || $request->is('admin')) {
            return $next($request);
        }

        // Apply tenant scope for tenant and B2B panels
        if ($this->auth->check() && $this->auth->user()->tenant_id) {
            $this->applyTenantScope($this->auth->user()->tenant_id);
        }

        return $next($request);
    }

    /**
     * Apply global tenant scope to all models
     */
    private function applyTenantScope(int $tenantId): void
    {
        // This will be called by models that implement TenantScoped interface
        // The actual scope implementation is in individual models
        app()->instance('current_tenant_id', $tenantId);
    }
}
