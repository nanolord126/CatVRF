<?php declare(strict_types=1);

namespace App\Domains\Hotels\Http\Controllers;

use App\Http\Controllers\Controller;

final class PricingRuleController extends Controller
{


    public function __construct(
            private readonly FraudControlService $fraud) {}

        public function store(\Illuminate\Http\Request $request, string $hotelId): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'operation', amount: 0, correlationId: $correlationId ?? '');

            try {
                $this->authorize('create', PricingRule::class);

                $data = $request->validate([
                    'room_type_id' => 'required|uuid',
                    'name' => 'required|string',
                    'type' => 'required|in:seasonal,length_of_stay,advance_booking,last_minute',
                    'date_from' => 'nullable|date',
                    'date_to' => 'nullable|date',
                    'multiplier' => 'required|numeric|min:0.5|max:2',
                    'min_nights' => 'nullable|integer',
                    'advance_days' => 'nullable|integer',
                ]);

                $rule = PricingRule::create([
                    'tenant_id' => tenant()->id,
                    'room_type_id' => $data['room_type_id'],
                    'name' => $data['name'],
                    'type' => $data['type'],
                    'date_from' => $data['date_from'] ?? null,
                    'date_to' => $data['date_to'] ?? null,
                    'multiplier' => $data['multiplier'],
                    'min_nights' => $data['min_nights'] ?? null,
                    'advance_days' => $data['advance_days'] ?? null,
                    'is_active' => true,
                    'correlation_id' => \Illuminate\Support\Str::uuid(),
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $rule,
                ], 201);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'error' => $e->getMessage(),
                ], 500);
            }
        }

        public function update(\Illuminate\Http\Request $request, string $id): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'operation', amount: 0, correlationId: $correlationId ?? '');

            try {
                $rule = PricingRule::findOrFail($id);
                $this->authorize('update', $rule);

                $data = $request->validate([
                    'name' => 'nullable|string',
                    'multiplier' => 'nullable|numeric|min:0.5|max:2',
                    'date_from' => 'nullable|date',
                    'date_to' => 'nullable|date',
                    'is_active' => 'nullable|boolean',
                ]);

                $rule->update($data);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $rule,
                ]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'error' => $e->getMessage(),
                ], 500);
            }
        }

        public function destroy(\Illuminate\Http\Request $request, string $id): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'operation', amount: 0, correlationId: $correlationId ?? '');

            try {
                $rule = PricingRule::findOrFail($id);
                $this->authorize('delete', $rule);

                $rule->delete();

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                ]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'error' => $e->getMessage(),
                ], 500);
            }
        }
}
