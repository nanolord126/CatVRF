<?php declare(strict_types=1);

namespace App\Domains\ShortTermRentals\Http\Controllers;

use App\Http\Controllers\Controller;

final class B2BController extends Controller
{

    public function __construct(
            private readonly ApartmentService $apartmentService,
            private readonly FraudControlService $fraud
        ) {}

        public function manageListings(Request $request): JsonResponse
        {
            $correlationId = (string) Str::uuid();

            try {
                $isB2B = $request->has('inn') && $request->has('business_card_id');
                if (!$isB2B) {
                    return new \Illuminate\Http\JsonResponse(['error' => 'B2B only', 'correlation_id' => $correlationId], 403);
                }

                $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');

                $listings = $this->apartmentService->getB2BListings($request->all(), $correlationId);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $listings,
                    'correlation_id' => $correlationId
                ]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse([
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId
                ], 403);
            }
        }

    /**
     * Get the string representation of this instance.
     *
     * @return string The string representation
     */
    public function __toString(): string
    {
        return static::class;
    }

    /**
     * Get debug information for this instance.
     *
     * @return array<string, mixed> Debug data including class name and state
     */
    public function toDebugArray(): array
    {
        return [
            'class' => static::class,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
