<?php declare(strict_types=1);

namespace App\Domains\Auto\Http\Controllers;

use App\Domains\Auto\Models\Taxi;
use App\Domains\Auto\Services\TaxiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class TaxiController
{
    public function __construct(private readonly TaxiService $service) {}

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
        if (class_exists('\App\Services\FraudControlService')) {
            \App\Services\FraudControlService::check();
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
            \Log::channel('audit')->error('Taxi creation failed', ['correlation_id' => $correlationId, 'error' => $e->getMessage()]);

            return response()->json(['error' => 'Failed to create taxi'], 422);
        }
    }

    public function update(Request $request, Taxi $taxi): JsonResponse
    {
        if (class_exists('\App\Services\FraudControlService')) {
            \App\Services\FraudControlService::check();
        }

        $this->authorize('update', $taxi);
        $correlationId = Str::uuid()->toString();

        try {
            $taxi->update($request->only(['name', 'phone', 'status']));
            \Log::channel('audit')->info('Taxi updated', ['correlation_id' => $correlationId, 'taxi_id' => $taxi->id]);

            return response()->json(['data' => $taxi]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to update taxi'], 422);
        }
    }

    public function destroy(Taxi $taxi): JsonResponse
    {
        if (class_exists('\App\Services\FraudControlService')) {
            \App\Services\FraudControlService::check();
        }

        $this->authorize('delete', $taxi);
        $correlationId = Str::uuid()->toString();

        try {
            $taxi->delete();
            \Log::channel('audit')->info('Taxi deleted', ['correlation_id' => $correlationId, 'taxi_id' => $taxi->id]);

            return response()->json(null, 204);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to delete taxi'], 422);
        }
    }
}
