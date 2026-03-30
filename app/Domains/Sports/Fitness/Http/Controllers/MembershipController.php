<?php declare(strict_types=1);

namespace App\Domains\Sports\Fitness\Http\Controllers;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class MembershipController extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            private readonly MembershipService $membershipService,
            private readonly FraudControlService $fraudControlService,
        ) {}

        public function store(): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $this->fraudControlService->check(auth()->id() ?? 0, 'operation', 0, request()->ip(), null, $correlationId);
            try {
                request()->validate([
                    'gym_id' => 'required|exists:gyms,id',
                    'type' => 'required|in:monthly,quarterly,annual',
                    'amount' => 'required|numeric',
                ]);

                $membership = $this->membershipService->createMembership(
                    request('gym_id'),
                    auth()->id(),
                    request('type'),
                    request('amount'),
                    $correlationId
                );

                return response()->json(['success' => true, 'data' => $membership, 'correlation_id' => $correlationId], 201);
            } catch (Throwable $e) {
                Log::channel('audit')->error('Failed to create membership', ['error' => $e->getMessage(), 'correlation_id' => $correlationId]);
                return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
            }
        }

        public function myMemberships(): JsonResponse
        {
            try {
                $memberships = Membership::where('member_id', auth()->id())
                    ->with(['gym'])
                    ->paginate(20);

                return response()->json(['success' => true, 'data' => $memberships, 'correlation_id' => Str::uuid()]);
            } catch (Throwable $e) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            }
        }

        public function show(int $id): JsonResponse
        {
            try {
                $membership = Membership::with(['gym'])->findOrFail($id);
                $this->authorize('view', $membership);

                return response()->json(['success' => true, 'data' => $membership, 'correlation_id' => Str::uuid()]);
            } catch (Throwable $e) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 404);
            }
        }

        public function update(int $id): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $this->fraudControlService->check(auth()->id() ?? 0, 'operation', 0, request()->ip(), null, $correlationId);
            try {
                $membership = Membership::findOrFail($id);
                $this->authorize('view', $membership);

                $membership->update(array_merge(request()->except(['id', 'tenant_id', 'business_group_id', 'correlation_id']), ['correlation_id' => $correlationId]));

                Log::channel('audit')->info('Membership updated', ['membership_id' => $id, 'correlation_id' => $correlationId]);

                return response()->json(['success' => true, 'data' => $membership, 'correlation_id' => $correlationId]);
            } catch (Throwable $e) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
            }
        }

        public function cancel(int $id): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            try {
                $membership = Membership::findOrFail($id);
                $this->authorize('cancel', $membership);

                $this->membershipService->cancelMembership($membership, request('reason', 'User requested'), $correlationId);

                Log::channel('audit')->info('Membership cancelled', ['membership_id' => $id, 'correlation_id' => $correlationId]);

                return response()->json(['success' => true, 'correlation_id' => $correlationId]);
            } catch (Throwable $e) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
            }
        }

        public function expire(int $id): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            try {
                $membership = Membership::findOrFail($id);
                $membership->update(['status' => 'expired', 'correlation_id' => $correlationId]);

                return response()->json(['success' => true, 'correlation_id' => $correlationId]);
            } catch (Throwable $e) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
            }
        }

        public function bookClass(int $classId): JsonResponse
        {
            return response()->json(['success' => true, 'correlation_id' => Str::uuid()], 201);
        }
}
