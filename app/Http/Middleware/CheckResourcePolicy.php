<?php declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

final class CheckResourcePolicy
{
    /**
     * Handle an incoming request.
     */
    public function handle(
        Request $request,
        Closure $next,
        string $policyClass,
        string $ability = 'view'
    ): Response {
        $user = $request->user();

        if (! $user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Extract resource from request
        $resourceId = $request->route('resource') ?? $request->route('id');

        if ($resourceId) {
            // Resolve policy and resource
            $resource = $this->resolveResource($policyClass, $resourceId);

            if (! $resource) {
                $this->log->channel('audit')->warning('Resource not found for policy check', [
                    'correlation_id' => $request->header('X-Correlation-ID'),
                    'policy_class' => $policyClass,
                    'resource_id' => $resourceId,
                    'user_id' => $user->id,
                    'ability' => $ability,
                ]);

                return response()->json(['error' => 'Resource not found'], 404);
            }

            // Authorize with policy
            if (! $this->gate->authorize($ability, $resource)) {
                $this->log->channel('audit')->warning('Policy authorization failed', [
                    'correlation_id' => $request->header('X-Correlation-ID'),
                    'policy_class' => $policyClass,
                    'ability' => $ability,
                    'user_id' => $user->id,
                    'user_roles' => $user->getRoleNames()->toArray(),
                    'resource_id' => $resourceId,
                ]);

                return response()->json(['error' => 'Forbidden - Policy check failed'], 403);
            }

            $this->log->channel('audit')->debug('Policy authorization granted', [
                'correlation_id' => $request->header('X-Correlation-ID'),
                'policy_class' => $policyClass,
                'ability' => $ability,
                'user_id' => $user->id,
            ]);
        }

        return $next($request);
    }

    /**
     * Resolve resource instance from model and ID
     */
    private function resolveResource(string $policyClass, mixed $resourceId): mixed
    {
        // Extract model class from policy class
        $modelClass = str_replace('Policy', '', str_replace('Policies\Domains\\', 'Domains\\', $policyClass));

        if (class_exists($modelClass)) {
            $resource = $modelClass::find($resourceId);
            if (!$resource) {
                throw new \Illuminate\Database\Eloquent\ModelNotFoundException("Resource [{$modelClass}] #{$resourceId} not found.");
            }
            return $resource;
        }

        throw new \RuntimeException("Policy model class [{$modelClass}] does not exist. Check policy naming convention.");
    }
}
