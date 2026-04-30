<?php declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Contracts\Auth\Guard;
use Symfony\Component\HttpFoundation\Response;

/**
 * Medical Compliance Middleware
 * 
 * Restricts access to medical resources based on compliance role.
 * Compliance officers have read-only access and cannot modify medical data.
 * 
 * @package App\Http\Middleware
 */
final readonly class MedicalComplianceMiddleware
{
    public function __construct(
        private readonly Guard $auth,
    ) {}
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $this->auth->user();

        if (!$user) {
            return $next($request);
        }

        // Check if user is compliance officer
        if ($user->hasRole('medical_compliance')) {
            $method = $request->method();
            $path = $request->path();

            // Compliance officers can only use GET requests (read-only)
            if (!in_array($method, ['GET', 'HEAD', 'OPTIONS'])) {
                // Allow GET only for medical resources
                if ($this->isMedicalResource($path)) {
                    abort(403, 'Compliance officers have read-only access to medical records.');
                }
            }
        }

        return $next($request);
    }

    /**
     * Check if the request is for a medical resource
     */
    private function isMedicalResource(string $path): bool
    {
        $medicalPaths = [
            'medical-records',
            'ai-diagnostic-logs',
            'emergency-logs',
            'doctors',
            'clinics',
            'medical-appointments',
        ];

        foreach ($medicalPaths as $medicalPath) {
            if (str_contains($path, $medicalPath)) {
                return true;
            }
        }

        return false;
    }
}
