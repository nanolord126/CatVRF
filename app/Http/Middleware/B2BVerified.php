<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domains\Supermarket\Models\B2BCompany;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Contracts\Routing\ResponseFactory;

/**
 * B2BVerified — проверка статуса B2B компании.
 *
 * Проверяет, что пользователь имеет подтверждённую B2B компанию.
 * Добавляет в Request:
 *   - b2b_company = B2BCompany объект
 *   - b2b_company_id = ID компании
 *
 * Использование:
 * Route::middleware(['auth:sanctum', 'b2b.verified'])->group(function () { ... });
 */
final class B2BVerified
{
    public function __construct(
        private readonly ResponseFactory $response,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return $this->response->json([
                'error' => 'Unauthenticated',
                'message' => 'Authentication required',
            ], 401);
        }

        // Ищем B2B компанию пользователя
        $company = B2BCompany::where('user_id', $user->id)->first();

        if (!$company) {
            return $this->response->json([
                'error' => 'B2B company not found',
                'message' => 'Please register your B2B company first',
            ], 403);
        }

        if (!$company->isApproved()) {
            if ($company->isPending()) {
                return $this->response->json([
                    'error' => 'Company pending approval',
                    'message' => 'Your company is under review',
                ], 403);
            }

            if ($company->isRejected()) {
                return $this->response->json([
                    'error' => 'Company rejected',
                    'message' => 'Your company application was rejected',
                    'rejection_reason' => $company->rejection_reason,
                ], 403);
            }

            return $this->response->json([
                'error' => 'Company not approved',
                'message' => 'Your company is not approved for B2B purchases',
            ], 403);
        }

        // Обогащаем Request данными о компании
        $request->attributes->set('b2b_company', $company);
        $request->merge([
            'b2b_company_id' => $company->id,
            'discount_level' => $company->discount_level,
        ]);

        return $next($request);
    }
}
