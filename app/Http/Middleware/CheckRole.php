declare(strict_types=1);

<?php
declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Auth\Access\AuthorizationException;

final /**
 * CheckRole
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): mixed
    {
        if (!auth()->check()) {
            throw new AuthorizationException('Unauthorized');
        }

        /** @var \App\Models\User $user */
        $user = auth()->user();

        $tenant = tenant();

        $userRole = null;
        if ($tenant) {
            $userRole = $user->getRoleInTenant($tenant->id)?->value;
        }

        $userRole = $userRole ?? $user->role?->value ?? 'customer';

        if (!in_array($userRole, $roles, true) && !$user->isPlatformAdmin()) {
            throw new AuthorizationException('Forbidden: Insufficient role');
        }

        return $next($request);
    }
}
