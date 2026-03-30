<?php declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CacheWarmerController extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function warm(CacheWarmerRequest $request): JsonResponse
        {
            $correlationId = $request->header('X-Correlation-ID') ?? \Illuminate\Support\Str::uuid()->toString();

            if ($userId = $request->input('user_id')) {
                dispatch(new WarmUserTasteProfileJob($userId));
            }

            if ($vertical = $request->input('vertical')) {
                dispatch(new WarmPopularProductsJob($vertical));
            }

            return response()->json([
                'success' => true,
                'message' => 'Cache warming job queued',
                'correlation_id' => $correlationId,
            ], 202);
        }
}
