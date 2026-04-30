<?php declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Log\LogManager;
use Symfony\Component\HttpFoundation\Response;

final class CanAccessVertical
{
    public function __construct(
        private readonly LogManager $log,
    ) {}
    /**
     * List of verticals and their required roles
     */
    private const VERTICAL_ROLES = [
        'medical' => ['super_admin', 'tenant_owner', 'tenant_manager', 'medical_provider'],
        'beauty' => ['super_admin', 'tenant_owner', 'tenant_manager', 'beauty_provider'],
        'food' => ['super_admin', 'tenant_owner', 'tenant_manager', 'restaurant_owner'],
        'real-estate' => ['super_admin', 'tenant_owner', 'tenant_manager', 'realtor'],
        'fashion' => ['super_admin', 'tenant_owner', 'tenant_manager', 'fashion_seller'],
        'travel' => ['super_admin', 'tenant_owner', 'tenant_manager', 'travel_agent'],
        'auto' => ['super_admin', 'tenant_owner', 'tenant_manager', 'auto_dealer'],
        'hotels' => ['super_admin', 'tenant_owner', 'tenant_manager', 'hotel_owner'],
        'electronics' => ['super_admin', 'tenant_owner', 'tenant_manager', 'electronics_seller'],
        'fitness' => ['super_admin', 'tenant_owner', 'tenant_manager', 'fitness_trainer'],
        'sports' => ['super_admin', 'tenant_owner', 'tenant_manager', 'sports_coach'],
        'luxury' => ['super_admin', 'tenant_owner', 'tenant_manager', 'luxury_seller'],
        'insurance' => ['super_admin', 'tenant_owner', 'tenant_manager', 'insurance_agent'],
        'legal' => ['super_admin', 'tenant_owner', 'tenant_manager', 'lawyer'],
        'logistics' => ['super_admin', 'tenant_owner', 'tenant_manager', 'logistics_provider'],
        'education' => ['super_admin', 'tenant_owner', 'tenant_manager', 'educator'],
        'crm' => ['super_admin', 'tenant_owner', 'tenant_manager', 'crm_user'],
        'delivery' => ['super_admin', 'tenant_owner', 'tenant_manager', 'courier'],
        'payment' => ['super_admin', 'tenant_owner', 'tenant_manager', 'payment_processor'],
        'analytics' => ['super_admin', 'tenant_owner', 'tenant_manager', 'analyst'],
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $vertical): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Authentication required',
            ], 401);
        }

        // Super admins can access everything
        if ($user->role?->value === 'super_admin') {
            return $next($request);
        }

        // Check if user has role for this vertical
        $allowedRoles = self::VERTICAL_ROLES[$vertical] ?? ['super_admin'];
        $userRole = $user->role?->value;

        if (!in_array($userRole, $allowedRoles, true)) {
            $this->log->channel('audit')->warning('Vertical access denied', [
                'user_id' => $user->id,
                'user_role' => $userRole,
                'vertical' => $vertical,
                'allowed_roles' => $allowedRoles,
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'error' => 'Forbidden',
                'message' => 'You do not have permission to access this vertical',
            ], 403);
        }

        return $next($request);
    }
}
