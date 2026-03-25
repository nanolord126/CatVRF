<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\AI;

use App\Enums\AI\ConstructorType;
use App\Http\Controllers\Controller;
use App\Http\Requests\AI\RunConstructorRequest;
use App\Services\AI\AIConstructorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

final class AIConstructorController extends Controller
{
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
