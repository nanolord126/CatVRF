<?php declare(strict_types=1);

namespace App\Domains\Auto\Http\Controllers;


use Psr\Log\LoggerInterface;
use App\Http\Controllers\Controller;

final class VehicleInsuranceController extends Controller
{

    public function __construct(private readonly FraudControlService $fraud,
            private readonly InsuranceCalculatorService $calculator,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger) {}

        public function index(Request $request): JsonResponse
        {
            $correlationId = Str::uuid()->toString();

            try {
                $insurances = VehicleInsurance::query()
                    ->when($request->status, fn($q) => $q->where('status', $request->status))
                    ->when($request->owner_id, fn($q) => $q->where('owner_id', $request->owner_id))
                    ->with(['vehicle', 'owner'])
                    ->paginate(20);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $insurances,
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Vehicle insurance index failed', [
                    'correlation_id' => $correlationId,
                    'error' => $e->getMessage(),
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Failed to retrieve vehicle insurances',
                    'correlation_id' => $correlationId,
                ], 500);
            }
        }

        public function calculate(Request $request): JsonResponse
        {
            $correlationId = Str::uuid()->toString();

            $validated = $request->validate([
                'vehicle_id' => 'required|exists:vehicles,id',
                'insurance_type' => 'required|in:osago,kasko,full',
                'coverage_amount' => 'required|integer|min:100000',
                'duration_months' => 'required|integer|min:3|max:12',
            ]);

            try {
                $premium = $this->calculator->calculatePremium(
                    $validated['vehicle_id'],
                    $validated['insurance_type'],
                    $validated['coverage_amount'],
                    $validated['duration_months']
                );

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => [
                        'premium_amount' => $premium,
                        'coverage_amount' => $validated['coverage_amount'],
                        'duration_months' => $validated['duration_months'],
                    ],
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Insurance calculation failed', [
                    'correlation_id' => $correlationId,
                    'error' => $e->getMessage(),
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Failed to calculate insurance premium',
                    'correlation_id' => $correlationId,
                ], 500);
            }
        }

        public function store(Request $request): JsonResponse
        {
            $correlationId = Str::uuid()->toString();

            $validated = $request->validate([
                'vehicle_id' => 'required|exists:vehicles,id',
                'owner_id' => 'required|exists:users,id',
                'insurance_type' => 'required|in:osago,kasko,full',
                'insurance_company' => 'required|string',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after:start_date',
                'coverage_amount' => 'required|integer|min:100000',
                'premium_amount' => 'required|integer|min:0',
            ]);

            try {
                $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'amount', amount: 0, correlationId: $correlationId ?? '');

                $insurance = $this->db->transaction(function () use ($validated, $correlationId) {
                    return VehicleInsurance::create([
                        ...$validated,
                        'tenant_id' => tenant()->id,
                        'status' => 'active',
                        'payment_status' => 'pending',
                        'policy_number' => 'POL-' . strtoupper(Str::random(10)),
                        'uuid' => Str::uuid()->toString(),
                        'correlation_id' => $correlationId,
                    ]);
                });

                $this->logger->info('Vehicle insurance created', [
                    'correlation_id' => $correlationId,
                    'insurance_id' => $insurance->id,
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $insurance->load(['vehicle', 'owner']),
                    'correlation_id' => $correlationId,
                ], 201);
            } catch (\Throwable $e) {
                $this->logger->error('Vehicle insurance creation failed', [
                    'correlation_id' => $correlationId,
                    'error' => $e->getMessage(),
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Failed to create vehicle insurance',
                    'correlation_id' => $correlationId,
                ], 500);
            }
        }

        public function show(VehicleInsurance $insurance): JsonResponse
        {
            return new \Illuminate\Http\JsonResponse([
                'success' => true,
                'data' => $insurance->load(['vehicle', 'owner']),
            ]);
        }

        public function destroy(VehicleInsurance $insurance): JsonResponse
        {
            $correlationId = Str::uuid()->toString();

            try {
                $insurance->delete();

                $this->logger->info('Vehicle insurance deleted', [
                    'correlation_id' => $correlationId,
                    'insurance_id' => $insurance->id,
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'message' => 'Vehicle insurance deleted',
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Vehicle insurance deletion failed', [
                    'correlation_id' => $correlationId,
                    'error' => $e->getMessage(),
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Failed to delete vehicle insurance',
                    'correlation_id' => $correlationId,
                ], 500);
            }
        }
}
