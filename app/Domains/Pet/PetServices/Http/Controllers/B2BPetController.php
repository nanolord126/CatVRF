<?php declare(strict_types=1);

namespace App\Domains\Pet\PetServices\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Domains\Pet\PetServices\Models\B2BPetStorefront;
use App\Domains\Pet\PetServices\Models\B2BPetOrder;
use Illuminate\Database\DatabaseManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class B2BPetController extends Controller
{
    public function __construct(
        private readonly DatabaseManager $db) {}

    public function storefronts(): JsonResponse
    {
        $correlationId = Str::uuid()->toString();

        $data = B2BPetStorefront::where('is_active', true)
            ->where('is_verified', true)
            ->paginate(20);

        return new JsonResponse([
            'success' => true,
            'data' => $data,
            'correlation_id' => $correlationId,
        ]);
    }

    public function createStorefront(Request $request): JsonResponse
    {
        $this->authorize('createStorefront', B2BPetStorefront::class);

        $validated = $request->validate([
            'company_name' => 'required',
            'inn' => 'required|unique:b2b_pet_storefronts,inn',
            'description' => 'nullable',
            'service_categories' => 'nullable|json',
            'wholesale_discount' => 'nullable|numeric|between:0,100',
            'min_order_amount' => 'integer|min:1000',
        ]);

        $correlationId = Str::uuid()->toString();

        $this->db->transaction(function () use ($validated, $correlationId, $request): void {
            B2BPetStorefront::create(array_merge(
                [
                    'uuid' => Str::uuid()->toString(),
                    'tenant_id' => $request->user()->tenant_id,
                    'correlation_id' => $correlationId,
                ],
                $validated,
            ));
        });

        return new JsonResponse([
            'success' => true,
            'message' => 'Витрина создана',
            'correlation_id' => $correlationId,
        ], 201);
    }

    public function createOrder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'b2b_pet_storefront_id' => 'required|exists:b2b_pet_storefronts,id',
            'company_contact_person' => 'required',
            'company_phone' => 'required',
            'items_json' => 'required|json',
            'total_amount' => 'required|numeric|min:1',
        ]);

        $correlationId = Str::uuid()->toString();

        $this->db->transaction(function () use ($validated, $correlationId, $request): void {
            B2BPetOrder::create(array_merge(
                [
                    'uuid' => Str::uuid()->toString(),
                    'tenant_id' => $request->user()->tenant_id,
                    'order_number' => 'B2B-' . Str::random(8),
                    'commission_amount' => (int) ($validated['total_amount'] * 0.14),
                    'status' => 'pending',
                    'correlation_id' => $correlationId,
                ],
                $validated,
            ));
        });

        return new JsonResponse([
            'success' => true,
            'message' => 'Заказ создан',
            'correlation_id' => $correlationId,
        ], 201);
    }

    public function myB2BOrders(Request $request): JsonResponse
    {
        $correlationId = Str::uuid()->toString();

        $data = B2BPetOrder::where('tenant_id', $request->user()->tenant_id)
            ->latest()
            ->paginate(20);

        return new JsonResponse([
            'success' => true,
            'data' => $data,
            'correlation_id' => $correlationId,
        ]);
    }

    public function approveOrder(int $id): JsonResponse
    {
        $order = B2BPetOrder::findOrFail($id);
        $this->authorize('approveOrder', $order);

        $correlationId = Str::uuid()->toString();

        $this->db->transaction(function () use ($order): void {
            $order->update(['status' => 'approved']);
        });

        return new JsonResponse([
            'success' => true,
            'message' => 'Одобрено',
            'correlation_id' => $correlationId,
        ]);
    }

    public function rejectOrder(int $id, Request $request): JsonResponse
    {
        $order = B2BPetOrder::findOrFail($id);
        $this->authorize('rejectOrder', $order);

        $correlationId = Str::uuid()->toString();

        $this->db->transaction(function () use ($order, $request): void {
            $order->update([
                'status' => 'rejected',
                'notes' => $request->get('reason', ''),
            ]);
        });

        return new JsonResponse([
            'success' => true,
            'message' => 'Отклонено',
            'correlation_id' => $correlationId,
        ]);
    }

    public function verifyInn(int $id): JsonResponse
    {
        $this->authorize('verifyInn', B2BPetStorefront::class);

        $correlationId = Str::uuid()->toString();

        $this->db->transaction(function () use ($id): void {
            B2BPetStorefront::findOrFail($id)->update(['is_verified' => true]);
        });

        return new JsonResponse([
            'success' => true,
            'message' => 'Верифицировано',
            'correlation_id' => $correlationId,
        ]);
    }
}
