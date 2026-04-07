<?php declare(strict_types=1);

namespace App\Domains\Taxi\Http\Controllers;


use Psr\Log\LoggerInterface;
use App\Http\Controllers\Controller;

final class TaxiController extends Controller
{

    public function __construct(private readonly TaxiService $service,
            private readonly FraudControlService $fraud, private readonly LoggerInterface $logger) {}

        public function index(): JsonResponse
        {
            $taxis = Taxi::where('tenant_id', tenant()->id)->paginate();

            return new \Illuminate\Http\JsonResponse(['data' => $taxis]);
        }

        public function show(Taxi $taxi): JsonResponse
        {
            $this->authorize('view', $taxi);

            return new \Illuminate\Http\JsonResponse(['data' => $taxi]);
        }

        public function store(Request $request): JsonResponse
        {
            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'operation', amount: 0, correlationId: $correlationId ?? '');

            if ($fraudResult['decision'] === 'block') {
                $this->logger->warning('Operation blocked by fraud control', [
                    'correlation_id' => $correlationId,
                    'user_id'        => $request->user()?->id,
                    'score'          => $fraudResult['score'],
                ]);
                return new \Illuminate\Http\JsonResponse([
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

                return new \Illuminate\Http\JsonResponse(['data' => $taxi], 201);
            } catch (\Throwable $e) {
                $this->logger->error('Taxi creation failed', ['correlation_id' => $correlationId, 'error' => $e->getMessage()]);

                return new \Illuminate\Http\JsonResponse(['error' => 'Failed to create taxi'], 422);
            }
        }

        public function update(Request $request, Taxi $taxi): JsonResponse
        {
            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'operation', amount: 0, correlationId: $correlationId ?? '');

            if ($fraudResult['decision'] === 'block') {
                $this->logger->warning('Operation blocked by fraud control', [
                    'correlation_id' => $correlationId,
                    'user_id'        => $request->user()?->id,
                    'score'          => $fraudResult['score'],
                ]);
                return new \Illuminate\Http\JsonResponse([
                    'success'        => false,
                    'error'          => 'Операция заблокирована.',
                    'correlation_id' => $correlationId,
                ], 403);
            }

            $this->authorize('update', $taxi);
            $correlationId = Str::uuid()->toString();

            try {
                $taxi->update($request->only(['name', 'phone', 'status']));
                $this->logger->info('Taxi updated', ['correlation_id' => $correlationId, 'taxi_id' => $taxi->id]);

                return new \Illuminate\Http\JsonResponse(['data' => $taxi]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['error' => 'Failed to update taxi'], 422);
            }
        }

        public function destroy(Taxi $taxi): JsonResponse
        {
            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'operation', amount: 0, correlationId: $correlationId ?? '');

            if ($fraudResult['decision'] === 'block') {
                $this->logger->warning('Operation blocked by fraud control', [
                    'correlation_id' => $correlationId,
                    'user_id'        => $request->user()?->id,
                    'score'          => $fraudResult['score'],
                ]);
                return new \Illuminate\Http\JsonResponse([
                    'success'        => false,
                    'error'          => 'Операция заблокирована.',
                    'correlation_id' => $correlationId,
                ], 403);
            }

            $this->authorize('delete', $taxi);
            $correlationId = Str::uuid()->toString();

            try {
                $taxi->delete();
                $this->logger->info('Taxi deleted', ['correlation_id' => $correlationId, 'taxi_id' => $taxi->id]);

                return new \Illuminate\Http\JsonResponse(null, 204);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['error' => 'Failed to delete taxi'], 422);
            }
        }
}
