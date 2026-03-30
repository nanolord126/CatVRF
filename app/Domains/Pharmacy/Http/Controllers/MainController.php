<?php declare(strict_types=1);

namespace App\Domains\Pharmacy\Http\Controllers;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class MainController extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(private readonly PharmacyService $service) {}

        public function index(Request $request): JsonResponse
        {
            $correlationId = (string) Str::uuid();
            try {
                $isB2B = $request->has('inn') && $request->has('business_card_id');
                return response()->json(['data' => [], 'b2b' => $isB2B, 'correlation_id' => $correlationId]);
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()], 500);
            }
        }
}
