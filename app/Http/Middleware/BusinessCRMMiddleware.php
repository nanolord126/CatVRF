<?php declare(strict_types=1);

namespace App\Http\Middleware;

use Illuminate\Log\LogManager;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Routing\ResponseFactory;

final class BusinessCRMMiddleware
{
    public function __construct(
        private readonly LogManager $logger,
        private readonly Guard $guard,
        private readonly ResponseFactory $response,
    ) {}


    /**
         * Ensure user can access CRM functions only if tenant_owner or manager role
         */
        public function handle(Request $request, Closure $next): mixed
        {
            /** @var \App\Models\User $user */
            $user = $this->guard->user();

            if (!$user) {
                return $this->response->json([
                    'error' => 'Unauthorized',
                    'correlation_id' => $request->header('X-Correlation-ID'),
                ], 401);
            }

            // Check role: only owner, manager, accountant can access CRM
            $allowedRoles = ['admin', 'business_owner', 'manager', 'accountant'];
            $userRole = $user->role ?? 'employee';

            if (!in_array($userRole, $allowedRoles, true)) {
                $this->logger->channel('audit')->warning('CRM access denied', [
                    'user_id' => $user->id,
                    'role' => $userRole,
                    'path' => $request->path(),
                    'correlation_id' => $request->header('X-Correlation-ID'),
                ]);

                return $this->response->json([
                    'error' => 'Forbidden: insufficient role for CRM access',
                    'correlation_id' => $request->header('X-Correlation-ID'),
                ], 403);
            }

            // Verify tenant isolation
            $tenantId = $user->tenant_id ?? null;
            $requestTenantId = $request->attributes->get('tenant_id');

            if ($requestTenantId && $tenantId && $tenantId !== $requestTenantId) {
                $this->logger->channel('audit')->error('Tenant isolation violation', [
                    'user_tenant' => $tenantId,
                    'request_tenant' => $requestTenantId,
                    'correlation_id' => $request->header('X-Correlation-ID'),
                ]);

                return $this->response->json([
                    'error' => 'Forbidden: tenant mismatch',
                    'correlation_id' => $request->header('X-Correlation-ID'),
                ], 403);
            }

            return $next($request);
        }
}
