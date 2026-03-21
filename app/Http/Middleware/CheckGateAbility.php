#!/php declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

final class CheckGateAbility
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $ability): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Check gate
        if (! Gate::check($ability)) {
            Log::channel('audit')->warning('Gate authorization failed', [
                'correlation_id' => $request->header('X-Correlation-ID'),
                'ability' => $ability,
                'user_id' => $user->id,
                'user_roles' => $user->getRoleNames()->toArray(),
                'path' => $request->path(),
            ]);

            return response()->json(['error' => 'Forbidden - Insufficient permissions'], 403);
        }

        Log::channel('audit')->debug('Gate authorization granted', [
            'correlation_id' => $request->header('X-Correlation-ID'),
            'ability' => $ability,
            'user_id' => $user->id,
        ]);

        return $next($request);
    }
}
