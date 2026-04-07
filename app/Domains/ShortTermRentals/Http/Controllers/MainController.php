<?php declare(strict_types=1);

namespace App\Domains\ShortTermRentals\Http\Controllers;

use App\Http\Controllers\Controller;

final class MainController extends Controller
{

    public function __construct(
            private readonly ApartmentService $apartmentService,
            private readonly FraudControlService $fraud
        ) {}

        public function index(Request $request): JsonResponse
        {
            $correlationId = (string) Str::uuid();

            try {
                $isB2B = $request->has('inn') && $request->has('business_card_id');
                $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'index_apartments', amount: 0, correlationId: $correlationId ?? '');

                $apartments = $this->apartmentService->getActiveApartments(['is_b2b' => $isB2B]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $apartments,
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
