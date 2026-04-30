<?php

declare(strict_types=1);

namespace App\Http\Controllers\Supermarket;

use App\Domains\Supermarket\Models\B2BCompany;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class B2BCompanyController
{
    /**
     * Зарегистрировать B2B компанию.
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'inn' => 'required|string|digits:10|unique:b2b_companies,inn',
            'kpp' => 'nullable|string|digits:9',
            'legal_address' => 'required|string|max:500',
            'actual_address' => 'nullable|string|max:500',
            'contact_person' => 'required|string|max:255',
            'contact_phone' => 'required|string|max:20',
            'contact_email' => 'required|email|max:255',
        ]);

        // Проверяем, что у пользователя ещё нет компании
        $existingCompany = B2BCompany::where('user_id', $request->user()->id)->first();
        if ($existingCompany) {
            return response()->json([
                'error' => 'Company already exists',
                'message' => 'You already have a registered B2B company',
                'company_id' => $existingCompany->id,
                'status' => $existingCompany->status,
            ], 400);
        }

        $company = B2BCompany::create([
            'user_id' => $request->user()->id,
            'company_name' => $validated['company_name'],
            'inn' => $validated['inn'],
            'kpp' => $validated['kpp'],
            'legal_address' => $validated['legal_address'],
            'actual_address' => $validated['actual_address'],
            'contact_person' => $validated['contact_person'],
            'contact_phone' => $validated['contact_phone'],
            'contact_email' => $validated['contact_email'],
            'status' => 'pending',
            'discount_level' => 1,
        ]);

        return response()->json([
            'message' => 'Company registration submitted for review',
            'company_id' => $company->id,
            'status' => $company->status,
        ], 201);
    }

    /**
     * Получить статус компании.
     */
    public function status(Request $request): JsonResponse
    {
        $company = B2BCompany::where('user_id', $request->user()->id)->first();

        if (!$company) {
            return response()->json([
                'has_company' => false,
                'message' => 'No company registered',
            ], 404);
        }

        return response()->json([
            'has_company' => true,
            'company_id' => $company->id,
            'company_name' => $company->company_name,
            'inn' => $company->inn,
            'status' => $company->status,
            'discount_level' => $company->discount_level,
            'verified_at' => $company->verified_at,
            'rejection_reason' => $company->rejection_reason,
        ]);
    }
}
