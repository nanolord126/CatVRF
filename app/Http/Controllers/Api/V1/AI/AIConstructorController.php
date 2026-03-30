<?php declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\AI;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class AIConstructorController extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(private readonly AIConstructorService $constructorService)
        {
        }
        public function run(RunConstructorRequest $request): JsonResponse
        {
            $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());
            $result = $this->constructorService->run(
                ConstructorType::from($request->validated('constructor_type')),
                $request->user(),
                $request->validated('input_parameters', []),
                $request->file('image'),
                $correlationId
            );
            if (!$result['success']) {
                return response()->json([
                    'message' => $result['error'],
                    'correlation_id' => $correlationId,
                ], 422);
            }
            return response()->json([
                'message' => 'AI Constructor finished successfully.',
                'data' => $result,
                'correlation_id' => $correlationId,
            ]);
        }
}
