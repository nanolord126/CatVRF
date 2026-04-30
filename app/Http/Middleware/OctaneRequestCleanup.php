<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Log\LogManager;
use Symfony\Component\HttpFoundation\Response;

final readonly class OctaneRequestCleanup
{
    public function __construct(
        private readonly LogManager $log,
    ) {}
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Clean up request-specific state to prevent memory leaks
        $this->cleanupRequestState();

        return $response;
    }

    private function cleanupRequestState(): void
    {
        // Clear static caches that might accumulate
        if (class_exists('Laravel\Octane\Facades\Octane')) {
            \Laravel\Octane\Facades\Octane::flushTables();
        }

        // Clear any static variables in services
        // This is a safety measure - services should be designed to be stateless
        foreach (get_declared_classes() as $class) {
            if (str_starts_with($class, 'App\\Services\\') || str_starts_with($class, 'App\\Domains\\')) {
                $reflection = new \ReflectionClass($class);
                foreach ($reflection->getProperties(\ReflectionProperty::STATIC) as $property) {
                    if ($property->isPublic() || $property->isProtected()) {
                        $property->setValue(null);
                    }
                }
            }
        }
    }
}
