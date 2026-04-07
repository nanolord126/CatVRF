<?php declare(strict_types=1);

namespace App\Domains\RealEstate\Http\Controllers;


use Psr\Log\LoggerInterface;
use App\Http\Controllers\Controller;

final class SaleListingController extends Controller
{

    public function __construct(private readonly FraudControlService $fraud,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger) {}

        public function index(): JsonResponse
        {
            try {
                $listings = SaleListing::query()
                    ->where('status', 'active')
                    ->with('property')
                    ->paginate(20);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $listings,
                ]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false], 500);
            }
        }

        public function store(): JsonResponse
        {
            $correlationId = Str::uuid()->toString();

            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'sale_listing_create', amount: 0, correlationId: $correlationId ?? '');
            if ($fraudResult['decision'] === 'block') {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'error' => 'Операция заблокирована.', 'correlation_id' => $correlationId], 403);
            }

            try {
                $data = $request->validate([
                    'property_id'       => 'required|integer',
                    'sale_price'        => 'required|integer|min:1',
                    'commission_percent' => 'nullable|numeric|min:0|max:50',
                    'description'       => 'nullable|string',
                ]);

                $listing = $this->db->transaction(function () use ($data, $correlationId) {
                    return SaleListing::create([
                        ...$data,
                        'tenant_id'      => tenant()?->id ?? $request->user()?->tenant_id ?? 1,
                        'status'         => 'active',
                        'correlation_id' => $correlationId,
                        'uuid'           => Str::uuid(),
                    ]);
                });

                $this->logger->info('Sale listing created', [
                    'correlation_id' => $correlationId,
                    'listing_id'     => $listing->id,
                    'tenant_id'      => $listing->tenant_id,
                    'user_id'        => $request->user()?->id,
                    'property_id'    => $listing->property_id,
                    'sale_price'     => $listing->sale_price,
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success'        => true,
                    'data'           => $listing,
                    'correlation_id' => $correlationId,
                ], 201);
            } catch (\Illuminate\Validation\ValidationException $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'errors' => $e->errors(), 'correlation_id' => $correlationId], 422);
            } catch (\Throwable $e) {
                $this->logger->error('Sale listing create failed', ['correlation_id' => $correlationId, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Ошибка создания объявления.', 'correlation_id' => $correlationId], 500);
            }
        }

        public function destroy(SaleListing $saleListing): JsonResponse
        {
            $correlationId = Str::uuid()->toString();

            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'sale_listing_delete', amount: 0, correlationId: $correlationId ?? '');
            if ($fraudResult['decision'] === 'block') {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'error' => 'Операция заблокирована.', 'correlation_id' => $correlationId], 403);
            }

            try {
                $this->db->transaction(function () use ($saleListing) {
                    $saleListing->update(['status' => 'removed']);
                    $saleListing->delete();
                });

                $this->logger->info('Sale listing deleted', [
                    'correlation_id' => $correlationId,
                    'listing_id'     => $saleListing->id,
                    'tenant_id'      => $saleListing->tenant_id,
                    'user_id'        => $request->user()?->id,
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success'        => true,
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Sale listing delete failed', ['correlation_id' => $correlationId, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Ошибка удаления объявления.', 'correlation_id' => $correlationId], 500);
            }
        }
}
