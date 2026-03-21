<?php declare(strict_types=1);

namespace App\Domains\RealEstate\Http\Controllers;

use App\Domains\RealEstate\Models\MortgageApplication;
use App\Domains\RealEstate\Services\MortgageCalculatorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controller для управления заявками на ипотеку.
 * Production 2026.
 */
final class MortgageController
{
    public function __construct(
        private readonly MortgageCalculatorService $mortgageService,
    ) {}

    public function index(): JsonResponse
    {
        try {
            $applications = MortgageApplication::query()
                ->where('client_id', auth()->id())
                ->with('property')
                ->paginate(15);

            return response()->json([
                'success' => true,
                'data' => $applications,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false], 500);
        }
    }

    public function show(MortgageApplication $application): JsonResponse
    {
        $this->authorize('view', $application);

        return response()->json([
            'success' => true,
            'data' => $application->load('property'),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        if (class_exists('\App\Services\FraudControlService')) {
            \App\Services\FraudControlService::check();
        }

        try {
            $request->validate([
                'property_id' => 'required|exists:properties,id',
                'property_price' => 'required|integer',
                'initial_payment' => 'required|integer',
                'loan_term_months' => 'required|integer',
                'interest_rate' => 'required|numeric',
                'bank' => 'required|in:sberbank,vtb,gazprombank,other',
            ]);

            $application = MortgageApplication::create([
                'tenant_id' => tenant('id'),
                'property_id' => $request->get('property_id'),
                'client_id' => auth()->id(),
                'property_price' => $request->get('property_price'),
                'loan_amount' => $request->get('property_price') - $request->get('initial_payment'),
                'initial_payment' => $request->get('initial_payment'),
                'loan_term_months' => $request->get('loan_term_months'),
                'interest_rate' => $request->get('interest_rate'),
                'bank' => $request->get('bank'),
                'status' => 'draft',
            ]);

            return response()->json([
                'success' => true,
                'data' => $application,
            ], 201);
        } catch (\Throwable $e) {
            return response()->json(['success' => false], 500);
        }
    }

    public function calculate(Request $request): JsonResponse
    {
        try {
            $result = $this->mortgageService->calculateMortgage(
                (int) $request->get('property_price'),
                (int) $request->get('initial_payment'),
                (int) $request->get('loan_term_months'),
                (float) $request->get('interest_rate'),
            );

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false], 500);
        }
    }
}
