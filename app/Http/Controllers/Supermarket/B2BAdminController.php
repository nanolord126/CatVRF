<?php

declare(strict_types=1);

namespace App\Http\Controllers\Supermarket;

use App\Domains\Supermarket\Models\B2BCompany;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final readonly class B2BAdminController
{
    /**
     * Одобрить B2B компанию.
     */
    public function approveCompany(Request $request, int $companyId): JsonResponse
    {
        Gate::authorize('admin');

        $company = B2BCompany::findOrFail($companyId);

        if ($company->status === 'approved') {
            return response()->json([
                'error' => 'Company already approved',
            ], 400);
        }

        $company->update([
            'status' => 'approved',
            'verified_at' => now(),
        ]);

        return response()->json([
            'message' => 'Company approved successfully',
            'company_id' => $company->id,
            'status' => $company->status,
        ]);
    }

    /**
     * Отклонить B2B компанию.
     */
    public function rejectCompany(Request $request, int $companyId): JsonResponse
    {
        Gate::authorize('admin');

        $validated = $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        $company = B2BCompany::findOrFail($companyId);

        if ($company->status === 'rejected') {
            return response()->json([
                'error' => 'Company already rejected',
            ], 400);
        }

        $company->update([
            'status' => 'rejected',
            'rejection_reason' => ['reason' => $validated['reason'], 'rejected_at' => now()->toDateTimeString()],
        ]);

        return response()->json([
            'message' => 'Company rejected successfully',
            'company_id' => $company->id,
            'status' => $company->status,
        ]);
    }
}
