<?php declare(strict_types=1);

namespace App\Domains\Pet\Http\Controllers;


use Psr\Log\LoggerInterface;
use App\Http\Controllers\Controller;

final class PetReviewController extends Controller
{

    public function __construct(
            private readonly FraudControlService $fraud, private readonly LoggerInterface $logger) {}

        public function store(Request $request): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'operation', amount: 0, correlationId: $correlationId ?? '');

            try {

                $review = PetReview::create([
                    ...$request->validated(),
                    'tenant_id' => tenant()->id,
                    'reviewer_id' => $request->user()?->id,
                    'correlation_id' => $correlationId,
                    'uuid' => Str::uuid(),
                    'status' => 'pending',
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $review,
                    'correlation_id' => $correlationId,
                ], 201);
            } catch (\Throwable $e) {
                $this->logger->error('Failed to create review', ['error' => $e->getMessage()]);
                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Failed to create review',
                    'correlation_id' => Str::uuid(),
                ], 500);
            }
        }

        public function update(Request $request, $id): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'operation', amount: 0, correlationId: $correlationId ?? '');

            try {
                $review = PetReview::findOrFail($id);
                $this->authorize('update', $review);

                $review->update([
                    ...$request->validated(),
                    'correlation_id' => $correlationId,
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $review,
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Failed to update review',
                    'correlation_id' => Str::uuid(),
                ], 500);
            }
        }

        public function destroy($id): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'operation', amount: 0, correlationId: $correlationId ?? '');

            try {
                $review = PetReview::findOrFail($id);
                $this->authorize('delete', $review);
                $correlationId = Str::uuid()->toString();

                $review->delete();

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'message' => 'Review deleted',
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Failed to delete review',
                    'correlation_id' => Str::uuid(),
                ], 500);
            }
        }

        public function approve($id): JsonResponse
        {
            try {
                $this->authorize('approve', PetReview::class);
                $review = PetReview::findOrFail($id);
                $correlationId = Str::uuid()->toString();

                $review->update([
                    'status' => 'approved',
                    'correlation_id' => $correlationId,
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $review,
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Failed to approve review',
                    'correlation_id' => Str::uuid(),
                ], 500);
            }
        }

        public function reject($id): JsonResponse
        {
            try {
                $this->authorize('approve', PetReview::class);
                $review = PetReview::findOrFail($id);
                $correlationId = Str::uuid()->toString();

                $review->update([
                    'status' => 'rejected',
                    'correlation_id' => $correlationId,
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $review,
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Failed to reject review',
                    'correlation_id' => Str::uuid(),
                ], 500);
            }
        }

        public function getClinicReviews($clinicId): JsonResponse
        {
            try {
                $reviews = PetReview::where('clinic_id', $clinicId)
                    ->where('status', 'approved')
                    ->with(['reviewer', 'vet'])
                    ->paginate(15);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $reviews,
                    'correlation_id' => Str::uuid(),
                ]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Failed to retrieve reviews',
                    'correlation_id' => Str::uuid(),
                ], 500);
            }
        }

        public function getVetReviews($vetId): JsonResponse
        {
            try {
                $reviews = PetReview::where('vet_id', $vetId)
                    ->where('status', 'approved')
                    ->with(['reviewer', 'appointment'])
                    ->paginate(15);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $reviews,
                    'correlation_id' => Str::uuid(),
                ]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Failed to retrieve reviews',
                    'correlation_id' => Str::uuid(),
                ], 500);
            }
        }

        public function myReviews(): JsonResponse
        {
            try {
                $reviews = PetReview::where('reviewer_id', $request->user()?->id)
                    ->where('tenant_id', tenant()->id)
                    ->with(['clinic', 'vet'])
                    ->paginate(15);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $reviews,
                    'correlation_id' => Str::uuid(),
                ]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Failed to retrieve reviews',
                    'correlation_id' => Str::uuid(),
                ], 500);
            }
        }
}
