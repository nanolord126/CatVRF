<?php declare(strict_types=1);

namespace App\Domains\RealEstate\Http\Controllers;

use App\Http\Controllers\Controller;

final class MortgageController extends Controller
{

    public function __construct(
            private readonly MortgageCalculatorService $mortgageService,
            private readonly FraudControlService $fraud) {}

        public function index(): JsonResponse
        {
            try {
                $applications = MortgageApplication::query()
                    ->where('client_id', $request->user()?->id)
                    ->with('property')
                    ->paginate(15);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $applications,
                ]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false], 500);
            }
        }

        public function show(MortgageApplication $application): JsonResponse
        {
            $this->authorize('view', $application);

            return new \Illuminate\Http\JsonResponse([
                'success' => true,
                'data' => $application->load('property'),
            ]);
        }

        public function store(Request $request): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'operation', amount: 0, correlationId: $correlationId ?? '');

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
                    'tenant_id' => tenant()?->id,
                    'property_id' => $request->get('property_id'),
                    'client_id' => $request->user()?->id,
                    'property_price' => $request->get('property_price'),
                    'loan_amount' => $request->get('property_price') - $request->get('initial_payment'),
                    'initial_payment' => $request->get('initial_payment'),
                    'loan_term_months' => $request->get('loan_term_months'),
                    'interest_rate' => $request->get('interest_rate'),
                    'bank' => $request->get('bank'),
                    'status' => 'draft',
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $application,
                ], 201);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false], 500);
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

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $result,
                ]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false], 500);
            }
        }
}
