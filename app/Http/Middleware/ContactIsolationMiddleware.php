<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Exceptions\ContactAlreadyUsedException;
use App\Services\Protection\ProfileIsolationGuard;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Psr\Log\LoggerInterface;

/**
 * Contact Isolation Middleware
 *
 * Intercepts registration and contact change requests to enforce zero-duplicate policy.
 * Applied to: /register, /tenants/register-business, /auth/social, /profile/contact
 *
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise Security
 */
final readonly class ContactIsolationMiddleware
{
    public function __construct(
        private readonly ProfileIsolationGuard $guard,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  Request  $request  HTTP request
     * @param  Closure  $next  Next middleware
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function handle(Request $request, Closure $next)
    {
        try {
            $route = $request->route();
            $routeName = $route?->getName() ?? $request->path();

            // Guard client registration
            if ($this->isClientRegistrationRoute($routeName)) {
                $this->guard->guardClientRegistration(
                    $request->input('email'),
                    $request->input('phone'),
                );
            }

            // Guard business registration
            if ($this->isBusinessRegistrationRoute($routeName)) {
                $this->guard->guardBusinessRegistration(
                    $request->input('email'),
                    $request->input('phone'),
                );
            }

            // Guard social registration
            if ($this->isSocialRegistrationRoute($routeName)) {
                $this->guard->guardSocialRegistration(
                    $request->input('email'),
                    $request,
                );
            }

            // Guard contact change
            if ($this->isContactChangeRoute($routeName)) {
                $user = $request->user();
                if ($user) {
                    $this->guard->guardContactChange(
                        $user,
                        $request->input('email'),
                        $request->input('phone'),
                    );
                }
            }

            return $next($request);

        } catch (ContactAlreadyUsedException $e) {
            $this->logger->warning('Contact isolation middleware blocked request', [
                'route' => $routeName ?? $request->path(),
                'email' => $request->input('email'),
                'ip' => $request->ip(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'contact_already_used',
                'message' => $e->getMessage(),
                'existing_type' => $e->existingType?->value,
            ], 403);
        }
    }

    /**
     * Check if route is client registration.
     */
    private function isClientRegistrationRoute(string $routeName): bool
    {
        $protectedRoutes = [
            'api.register',
            'api.auth.register',
            'register',
            'auth.register',
        ];

        return in_array($routeName, $protectedRoutes, true)
            || str_contains($routeName, 'register')
            || str_contains($request->path(), 'register');
    }

    /**
     * Check if route is business registration.
     */
    private function isBusinessRegistrationRoute(string $routeName): bool
    {
        $protectedRoutes = [
            'api.tenants.register',
            'api.tenants.create',
            'tenants.register',
            'tenants.create',
        ];

        return in_array($routeName, $protectedRoutes, true)
            || str_contains($routeName, 'tenant')
            || str_contains($request->path(), 'tenant');
    }

    /**
     * Check if route is social registration.
     */
    private function isSocialRegistrationRoute(string $routeName): bool
    {
        $protectedRoutes = [
            'api.auth.social',
            'auth.social',
            'api.auth.oauth.callback',
        ];

        return in_array($routeName, $protectedRoutes, true)
            || str_contains($routeName, 'social')
            || str_contains($routeName, 'oauth');
    }

    /**
     * Check if route is contact change.
     */
    private function isContactChangeRoute(string $routeName): bool
    {
        $protectedRoutes = [
            'api.profile.update-contact',
            'api.user.update-email',
            'api.user.update-phone',
            'profile.contact',
        ];

        return in_array($routeName, $protectedRoutes, true)
            || str_contains($routeName, 'contact')
            || str_contains($routeName, 'email')
            || str_contains($routeName, 'phone');
    }
}
