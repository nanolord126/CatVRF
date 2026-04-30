<?php

declare(strict_types=1);

namespace Modules\Fitness\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Carbon\CarbonImmutable;
use Modules\Fitness\Application\Services\MembershipService;
use Modules\Fitness\Application\Services\AttendanceService;
use Modules\Fitness\Application\Services\ScheduleService;
use Modules\Fitness\Application\Services\TrainerCertificationService;
use Modules\Fitness\Application\Services\TrainerEffectivenessService;
use Modules\Fitness\Domain\Repositories\MembershipRepositoryInterface;
use Modules\Fitness\Domain\Repositories\ClientRepositoryInterface;
use Modules\Fitness\Domain\Enums\MembershipType;
use Modules\Fitness\Domain\Enums\MembershipStatus;
use Psr\Log\LoggerInterface;

final readonly class FitnessController
{
    public function __construct(
        private MembershipService $membershipService,
        private AttendanceService $attendanceService,
        private ScheduleService $scheduleService,
        private TrainerCertificationService $trainerCertificationService,
        private TrainerEffectivenessService $trainerEffectivenessService,
        private MembershipRepositoryInterface $membershipRepository,
        private ClientRepositoryInterface $clientRepository,
        private LoggerInterface $logger,
    ) {}

    public function createMembership(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'client_id' => 'required|integer|exists:fitness_clients,id',
                'type' => 'required|string|in:monthly,quarterly,annual,unlimited,10_visits,20_visits',
                'start_date' => 'required|date|after_or_equal:today',
                'end_date' => 'nullable|date|after:start_date',
                'price' => 'required|numeric|min:0',
                'total_visits' => 'nullable|integer|min:1',
                'allow_freeze' => 'boolean',
                'max_freeze_days' => 'nullable|integer|min:0|max:365',
                'notes' => 'nullable|string|max:1000',
            ]);

            $type = MembershipType::from($request->input('type'));
            $startDate = CarbonImmutable::parse($request->input('start_date'));
            $endDate = $request->input('end_date') 
                ? CarbonImmutable::parse($request->input('end_date')) 
                : null;

            $membership = $this->membershipService->createMembership(
                clientId: (int) $request->input('client_id'),
                type: $type,
                startDate: $startDate,
                endDate: $endDate,
                price: (float) $request->input('price'),
                totalVisits: $request->input('total_visits') ? (int) $request->input('total_visits') : null,
                allowFreeze: $request->input('allow_freeze', true),
                maxFreezeDays: $request->input('max_freeze_days', 30),
                notes: $request->input('notes'),
            );

            return new JsonResponse([
                'success' => true,
                'message' => 'Membership created successfully',
                'membership' => [
                    'id' => $membership->id,
                    'client_id' => $membership->clientId,
                    'type' => $membership->type->value,
                    'status' => $membership->status->value,
                    'start_date' => $membership->startDate->format('Y-m-d'),
                    'end_date' => $membership->endDate?->format('Y-m-d'),
                    'price' => $membership->price,
                    'remaining_visits' => $membership->remainingVisits,
                ],
            ], 201);
        } catch (ValidationException $e) {
            return new JsonResponse(['error' => 'Validation failed', 'details' => $e->errors()], 422);
        } catch (\Throwable $e) {
            $this->logger->error('Membership creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return new JsonResponse(['error' => 'Internal server error'], 500);
        }
    }

    public function freezeMembership(Request $request, int $membershipId): JsonResponse
    {
        try {
            $membership = $this->membershipService->freezeMembership($membershipId);

            return new JsonResponse([
                'success' => true,
                'message' => 'Membership frozen successfully',
                'membership' => [
                    'id' => $membership->id,
                    'status' => $membership->status->value,
                    'freeze_start_date' => $membership->freezeStartDate?->format('Y-m-d'),
                ],
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Membership freeze failed', [
                'membership_id' => $membershipId,
                'error' => $e->getMessage(),
            ]);
            return new JsonResponse(['error' => 'Internal server error'], 500);
        }
    }

    public function unfreezeMembership(Request $request, int $membershipId): JsonResponse
    {
        try {
            $membership = $this->membershipService->unfreezeMembership($membershipId);

            return new JsonResponse([
                'success' => true,
                'message' => 'Membership unfrozen successfully',
                'membership' => [
                    'id' => $membership->id,
                    'status' => $membership->status->value,
                    'end_date' => $membership->endDate->format('Y-m-d'),
                ],
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Membership unfreeze failed', [
                'membership_id' => $membershipId,
                'error' => $e->getMessage(),
            ]);
            return new JsonResponse(['error' => 'Internal server error'], 500);
        }
    }

    public function cancelMembership(Request $request, int $membershipId): JsonResponse
    {
        try {
            $membership = $this->membershipService->cancelMembership($membershipId);

            return new JsonResponse([
                'success' => true,
                'message' => 'Membership cancelled successfully',
                'membership' => [
                    'id' => $membership->id,
                    'status' => $membership->status->value,
                ],
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Membership cancellation failed', [
                'membership_id' => $membershipId,
                'error' => $e->getMessage(),
            ]);
            return new JsonResponse(['error' => 'Internal server error'], 500);
        }
    }

    public function useVisit(Request $request, int $membershipId): JsonResponse
    {
        try {
            $membership = $this->membershipService->useVisit($membershipId);

            return new JsonResponse([
                'success' => true,
                'message' => 'Visit used successfully',
                'membership' => [
                    'id' => $membership->id,
                    'remaining_visits' => $membership->remainingVisits,
                ],
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Visit usage failed', [
                'membership_id' => $membershipId,
                'error' => $e->getMessage(),
            ]);
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    public function getClientMemberships(Request $request, int $clientId): JsonResponse
    {
        try {
            $memberships = $this->membershipService->getClientMemberships($clientId);
            $active = $this->membershipService->getActiveMembership($clientId);

            return new JsonResponse([
                'success' => true,
                'memberships' => array_map(fn ($m) => [
                    'id' => $m->id,
                    'type' => $m->type->value,
                    'status' => $m->status->value,
                    'start_date' => $m->startDate->format('Y-m-d'),
                    'end_date' => $m->endDate?->format('Y-m-d'),
                    'remaining_visits' => $m->remainingVisits,
                ], $memberships),
                'active_membership' => $active ? [
                    'id' => $active->id,
                    'type' => $active->type->value,
                    'days_remaining' => $active->getDaysRemaining(),
                    'visits_remaining' => $active->getVisitsRemaining(),
                ] : null,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Client memberships retrieval failed', [
                'client_id' => $clientId,
                'error' => $e->getMessage(),
            ]);
            return new JsonResponse(['error' => 'Internal server error'], 500);
        }
    }

    public function getMembershipStats(Request $request, int $clientId): JsonResponse
    {
        try {
            $stats = $this->membershipService->getMembershipStats($clientId);

            return new JsonResponse([
                'success' => true,
                'stats' => $stats,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Membership stats retrieval failed', [
                'client_id' => $clientId,
                'error' => $e->getMessage(),
            ]);
            return new JsonResponse(['error' => 'Internal server error'], 500);
        }
    }

    public function extendMembership(Request $request, int $membershipId): JsonResponse
    {
        try {
            $request->validate([
                'days' => 'required|integer|min:1|max:365',
            ]);

            $membership = $this->membershipService->extendMembership(
                $membershipId,
                (int) $request->input('days')
            );

            return new JsonResponse([
                'success' => true,
                'message' => 'Membership extended successfully',
                'membership' => [
                    'id' => $membership->id,
                    'end_date' => $membership->endDate->format('Y-m-d'),
                ],
            ]);
        } catch (ValidationException $e) {
            return new JsonResponse(['error' => 'Validation failed', 'details' => $e->errors()], 422);
        } catch (\Throwable $e) {
            $this->logger->error('Membership extension failed', [
                'membership_id' => $membershipId,
                'error' => $e->getMessage(),
            ]);
            return new JsonResponse(['error' => 'Internal server error'], 500);
        }
    }
}
