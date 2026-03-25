<?php declare(strict_types=1);

namespace App\Domains\Taxi\Http\Controllers;

use App\Domains\Auto\Models\Taxi;
use App\Domains\Taxi\Services\TaxiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class TaxiController
{
    public function __construct(private readonly TaxiService $service,
        private readonly FraudControlService $fraudControlService,) {}

    public function index(): JsonResponse
    {
        $taxis = Taxi::where('tenant_id', tenant()->id)->paginate();

        return response()->json(['data' => $taxis]);
    }

    public function show(Taxi $taxi): JsonResponse
    {
        $this->authorize('view', $taxi);

        return response()->json(['data' => $taxi]);
    }

    public function store(Request $request): JsonResponse
    {
        $fraudResult = $this->fraudControlService->check(
            auth()->id() ?? 0,
            'operation',
            0,
            request()->ip(),
            request()->header('X-Device-Fingerprint'),
            $correlationId,
        );

        if ($fraudResult['decision'] === 'block') {
            $this->log->channel('fraud_alert')->warning('Operation blocked by fraud control', [
                'correlation_id' => $correlationId,
                'user_id'        => auth()->id(),
                'score'          => $fraudResult['score'],
            ]);
            return response()->json([
                'success'        => false,
                'error'          => 'Операция заблокирована.',
                'correlation_id' => $correlationId,
            ], 403);
        }

        $this->authorize('create', Taxi::class);
        $correlationId = Str::uuid()->toString();

        try {
            $taxi = $this->service->createDriver([
                'name' => $request->input('name'),
                'phone' => $request->input('phone'),
                'license' => $request->input('license'),
            ], tenant()->id, $correlationId);

            return response()->json(['data' => $taxi], 201);
        } catch (\Exception $e) {
            \$this->log->channel('audit')->error('Taxi creation failed', ['correlation_id' => $correlationId, 'error' => $e->getMessage()]);

            return response()->json(['error' => 'Failed to create taxi'], 422);
        }
    }

    public function update(Request $request, Taxi $taxi): JsonResponse
    {
        $fraudResult = $this->fraudControlService->check(
            auth()->id() ?? 0,
            'operation',
            0,
            request()->ip(),
            request()->header('X-Device-Fingerprint'),
            $correlationId,
        );

        if ($fraudResult['decision'] === 'block') {
            $this->log->channel('fraud_alert')->warning('Operation blocked by fraud control', [
                'correlation_id' => $correlationId,
                'user_id'        => auth()->id(),
                'score'          => $fraudResult['score'],
            ]);
            return response()->json([
                'success'        => false,
                'error'          => 'Операция заблокирована.',
                'correlation_id' => $correlationId,
            ], 403);
        }

        $this->authorize('update', $taxi);
        $correlationId = Str::uuid()->toString();

        try {
            $taxi->update($request->only(['name', 'phone', 'status']));
            \$this->log->channel('audit')->info('Taxi updated', ['correlation_id' => $correlationId, 'taxi_id' => $taxi->id]);

            return response()->json(['data' => $taxi]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to update taxi'], 422);
        }
    }

    public function destroy(Taxi $taxi): JsonResponse
    {
        $fraudResult = $this->fraudControlService->check(
            auth()->id() ?? 0,
            'operation',
            0,
            request()->ip(),
            request()->header('X-Device-Fingerprint'),
            $correlationId,
        );

        if ($fraudResult['decision'] === 'block') {
            $this->log->channel('fraud_alert')->warning('Operation blocked by fraud control', [
                'correlation_id' => $correlationId,
                'user_id'        => auth()->id(),
                'score'          => $fraudResult['score'],
            ]);
            return response()->json([
                'success'        => false,
                'error'          => 'Операция заблокирована.',
                'correlation_id' => $correlationId,
            ], 403);
        }

        $this->authorize('delete', $taxi);
        $correlationId = Str::uuid()->toString();

        try {
            $taxi->delete();
            \$this->log->channel('audit')->info('Taxi deleted', ['correlation_id' => $correlationId, 'taxi_id' => $taxi->id]);

            return response()->json(null, 204);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to delete taxi'], 422);
        }
    }
}
