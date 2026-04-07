<?php declare(strict_types=1);

namespace App\Domains\RealEstate\Http\Controllers;


use Psr\Log\LoggerInterface;
use App\Http\Controllers\Controller;

final class PropertyController extends Controller
{

    public function __construct(private readonly PropertySearchService $searchService,
            private readonly FraudControlService $fraud,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger) {}

        public function index(): JsonResponse
        {
            try {
                $correlationId = Str::uuid()->toString();
                $filters = $request->except(['id', 'tenant_id', 'business_group_id', 'correlation_id']);

                $properties = $this->searchService->searchProperties($filters, $correlationId);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $properties,
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Ошибка'], 500);
            }
        }

        public function show(Property $property): JsonResponse
        {
            try {
                $details = (new PropertySearchService())->getPropertyDetails($property);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $details,
                ]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Объект не найден'], 404);
            }
        }

        public function details(Property $property): JsonResponse
        {
            try {
                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'property' => $property->load(['rentalListing', 'saleListing', 'images', 'viewingAppointments']),
                    'images' => $property->images()->orderBy('sort_order')->get(),
                ]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false], 500);
            }
        }

        public function store(): JsonResponse
        {
            $correlationId = Str::uuid()->toString();

            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'property_create', amount: 0, correlationId: $correlationId ?? '');
            if ($fraudResult['decision'] === 'block') {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'error' => 'Операция заблокирована.', 'correlation_id' => $correlationId], 403);
            }

            try {
                $data = $request->validate([
                    'address'     => 'required|string|max:500',
                    'type'        => 'required|in:apartment,house,land,commercial',
                    'area'        => 'required|numeric|min:1',
                    'rooms'       => 'nullable|integer',
                    'floor'       => 'nullable|integer',
                    'description' => 'nullable|string',
                    'price'       => 'nullable|integer|min:0',
                    'geo_point'   => 'nullable|array',
                ]);

                $property = $this->db->transaction(function () use ($data, $correlationId) {
                    return Property::create([
                        ...$data,
                        'tenant_id'      => tenant()?->id ?? $request->user()?->tenant_id ?? 1,
                        'owner_id'       => $request->user()?->id,
                        'status'         => 'active',
                        'correlation_id' => $correlationId,
                        'uuid'           => Str::uuid(),
                    ]);
                });

                $this->logger->info('Property created', [
                    'correlation_id' => $correlationId,
                    'property_id'    => $property->id,
                    'tenant_id'      => $property->tenant_id,
                    'user_id'        => $request->user()?->id,
                    'type'           => $property->type,
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success'        => true,
                    'data'           => $property,
                    'correlation_id' => $correlationId,
                ], 201);
            } catch (\Illuminate\Validation\ValidationException $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'errors' => $e->errors(), 'correlation_id' => $correlationId], 422);
            } catch (\Throwable $e) {
                $this->logger->error('Property create failed', ['correlation_id' => $correlationId, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Ошибка создания объекта.', 'correlation_id' => $correlationId], 500);
            }
        }

        public function update(Property $property): JsonResponse
        {
            $correlationId = Str::uuid()->toString();

            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'property_update', amount: 0, correlationId: $correlationId ?? '');
            if ($fraudResult['decision'] === 'block') {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'error' => 'Операция заблокирована.', 'correlation_id' => $correlationId], 403);
            }

            try {
                $data = $request->validate([
                    'address'     => 'nullable|string|max:500',
                    'type'        => 'nullable|in:apartment,house,land,commercial',
                    'area'        => 'nullable|numeric|min:1',
                    'rooms'       => 'nullable|integer',
                    'floor'       => 'nullable|integer',
                    'description' => 'nullable|string',
                    'price'       => 'nullable|integer|min:0',
                    'status'      => 'nullable|in:active,sold,rented',
                ]);

                $before = $property->getAttributes();

                $this->db->transaction(function () use ($property, $data) {
                    $property->update($data);
                });

                $this->logger->info('Property updated', [
                    'correlation_id' => $correlationId,
                    'property_id'    => $property->id,
                    'tenant_id'      => $property->tenant_id,
                    'user_id'        => $request->user()?->id,
                    'before'         => $before,
                    'after'          => $data,
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success'        => true,
                    'data'           => $property->fresh(),
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Illuminate\Validation\ValidationException $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'errors' => $e->errors(), 'correlation_id' => $correlationId], 422);
            } catch (\Throwable $e) {
                $this->logger->error('Property update failed', ['correlation_id' => $correlationId, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Ошибка обновления объекта.', 'correlation_id' => $correlationId], 500);
            }
        }

        public function destroy(Property $property): JsonResponse
        {
            $correlationId = Str::uuid()->toString();

            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'property_delete', amount: 0, correlationId: $correlationId ?? '');
            if ($fraudResult['decision'] === 'block') {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'error' => 'Операция заблокирована.', 'correlation_id' => $correlationId], 403);
            }

            try {
                $this->db->transaction(function () use ($property) {
                    $property->delete();
                });

                $this->logger->info('Property deleted', [
                    'correlation_id' => $correlationId,
                    'property_id'    => $property->id,
                    'tenant_id'      => $property->tenant_id,
                    'user_id'        => $request->user()?->id,
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success'        => true,
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Property delete failed', ['correlation_id' => $correlationId, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Ошибка удаления объекта.', 'correlation_id' => $correlationId], 500);
            }
        }
}
