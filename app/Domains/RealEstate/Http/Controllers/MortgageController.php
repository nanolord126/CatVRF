<?php declare(strict_types=1);

namespace App\Domains\RealEstate\Http\Controllers;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class MortgageController extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            private readonly MortgageCalculatorService $mortgageService,
            private readonly FraudControlService $fraudControlService,
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
            $correlationId = Str::uuid()->toString();
            $this->fraudControlService->check(auth()->id() ?? 0, 'operation', 0, request()->ip(), null, $correlationId);

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
