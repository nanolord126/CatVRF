<?php declare(strict_types=1);

namespace App\Http\Middleware;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CheckRole extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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
