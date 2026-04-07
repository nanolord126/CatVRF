<?php declare(strict_types=1);

namespace App\Domains\Sports\Fitness\Http\Controllers;


use Psr\Log\LoggerInterface;
use App\Http\Controllers\Controller;

final class MembershipController extends Controller
{

    public function __construct(
            private readonly MembershipService $membershipService,
            private readonly FraudControlService $fraud, private readonly LoggerInterface $logger) {}

        public function store(): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'operation', amount: 0, correlationId: $correlationId ?? '');
            try {
                $request->validate([
                    'gym_id' => 'required|exists:gyms,id',
                    'type' => 'required|in:monthly,quarterly,annual',
                    'amount' => 'required|numeric',
                ]);

                $membership = $this->membershipService->createMembership(
                    $request->input('gym_id'),
                    $request->user()?->id,
                    $request->input('type'),
                    $request->input('amount'),
                    $correlationId
                );

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $membership, 'correlation_id' => $correlationId], 201);
            } catch (Throwable $e) {
                $this->logger->error('Failed to create membership', ['error' => $e->getMessage(), 'correlation_id' => $correlationId]);
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
            }
        }

        public function myMemberships(): JsonResponse
        {
            try {
                $memberships = Membership::where('member_id', $request->user()?->id)
                    ->with(['gym'])
                    ->paginate(20);

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $memberships, 'correlation_id' => Str::uuid()]);
            } catch (Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
            }
        }

        public function show(int $id): JsonResponse
        {
            try {
                $membership = Membership::with(['gym'])->findOrFail($id);
                $this->authorize('view', $membership);

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $membership, 'correlation_id' => Str::uuid()]);
            } catch (Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage()], 404);
            }
        }

        public function update(int $id): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'operation', amount: 0, correlationId: $correlationId ?? '');
            try {
                $membership = Membership::findOrFail($id);
                $this->authorize('view', $membership);

                $membership->update(array_merge($request->except(['id', 'tenant_id', 'business_group_id', 'correlation_id']), ['correlation_id' => $correlationId]));

                $this->logger->info('Membership updated', ['membership_id' => $id, 'correlation_id' => $correlationId]);

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $membership, 'correlation_id' => $correlationId]);
            } catch (Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
            }
        }

        public function cancel(int $id): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            try {
                $membership = Membership::findOrFail($id);
                $this->authorize('cancel', $membership);

                $this->membershipService->cancelMembership($membership, $request->input('reason', 'User requested'), $correlationId);

                $this->logger->info('Membership cancelled', ['membership_id' => $id, 'correlation_id' => $correlationId]);

                return new \Illuminate\Http\JsonResponse(['success' => true, 'correlation_id' => $correlationId]);
            } catch (Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
            }
        }

        public function expire(int $id): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            try {
                $membership = Membership::findOrFail($id);
                $membership->update(['status' => 'expired', 'correlation_id' => $correlationId]);

                return new \Illuminate\Http\JsonResponse(['success' => true, 'correlation_id' => $correlationId]);
            } catch (Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
            }
        }

        public function bookClass(int $classId): JsonResponse
        {
            return new \Illuminate\Http\JsonResponse(['success' => true, 'correlation_id' => Str::uuid()], 201);
        }
}
