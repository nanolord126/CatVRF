<?php declare(strict_types=1);

namespace App\Domains\ShortTermRentals\Http\Controllers;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class MainController extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            private readonly ApartmentService $apartmentService,
            private readonly FraudControlService $fraudControl
        ) {}

        public function index(Request $request): JsonResponse
        {
            $correlationId = (string) Str::uuid();

            try {
                $isB2B = $request->has('inn') && $request->has('business_card_id');
                $this->fraudControl->check($request->all(), 'index_apartments');

                $apartments = $this->apartmentService->getActiveApartments(['is_b2b' => $isB2B]);

                return response()->json([
                    'success' => true,
                    'data' => $apartments,
                    'correlation_id' => $correlationId
                ]);
            } catch (\Throwable $e) {
                return response()->json([
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId
                ], 403);
            }
        }
}
