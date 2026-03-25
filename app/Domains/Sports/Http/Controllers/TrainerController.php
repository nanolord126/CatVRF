declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Sports\Http\Controllers;

use App\Domains\Sports\Models\Trainer;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

final /**
 * TrainerController
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class TrainerController
{
    public function byStudio(int $studioId): JsonResponse
    {
        try {
            $trainers = Trainer::where('studio_id', $studioId)->where('is_active', true)->paginate(15);
            return response()->json(['success' => true, 'data' => $trainers, 'correlation_id' => Str::uuid()]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Failed to list trainers'], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $trainer = Trainer::with(['studio', 'reviews', 'classes'])->findOrFail($id);
            return response()->json(['success' => true, 'data' => $trainer, 'correlation_id' => Str::uuid()]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Trainer not found'], 404);
        }
    }
}
