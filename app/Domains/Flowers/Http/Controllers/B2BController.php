<?php declare(strict_types=1);

namespace App\Domains\Flowers\Http\Controllers;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class B2BController extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            private readonly B2BService $b2bService,
            private readonly FraudControlService $fraudControl
        ) {}

        public function createStorefront(Request $request): JsonResponse
        {
            $correlationId = (string) Str::uuid();

            try {
                $isB2B = $request->has('inn') && $request->has('business_card_id');
                if (!$isB2B) {
                    return response()->json(['error' => 'B2B only', 'correlation_id' => $correlationId], 403);
                }

                $this->fraudControl->check($request->all(), 'create_b2b_storefront');

                $storefront = $this->b2bService->createStorefront($request->all(), $correlationId);

                return response()->json([
                    'success' => true,
                    'data' => $storefront,
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
