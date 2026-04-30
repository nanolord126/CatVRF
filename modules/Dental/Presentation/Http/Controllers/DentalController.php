<?php

declare(strict_types=1);

namespace Modules\Dental\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Modules\Dental\Application\Services\TreatmentPlanService;
use Modules\Dental\Application\Services\DentalChartService;
use Modules\Dental\Application\Services\LabService;
use Modules\Dental\Domain\Repositories\TreatmentPlanRepositoryInterface;
use Modules\Dental\Domain\ValueObjects\PatientId;
use Modules\Dental\Domain\ValueObjects\DoctorId;
use Modules\Dental\Domain\ValueObjects\TenantId;
use Modules\Dental\Domain\ValueObjects\TreatmentPlanId;
use Modules\Dental\Domain\ValueObjects\ToothChartId;
use Modules\Dental\Domain\ValueObjects\Money;
use Psr\Log\LoggerInterface;

final readonly class DentalController
{
    public function __construct(
        private TreatmentPlanService $treatmentPlanService,
        private DentalChartService $dentalChartService,
        private LabService $labService,
        private TreatmentPlanRepositoryInterface $planRepository,
        private LoggerInterface $logger,
    ) {}

    public function createTreatmentPlan(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'patient_id' => 'required|integer',
                'doctor_id' => 'required|integer',
                'tenant_id' => 'required|integer',
                'name' => 'required|string|max:255',
                'description' => 'nullable|string|max:2000',
                'tooth_chart_id' => 'nullable|integer',
            ]);

            $plan = $this->treatmentPlanService->createPlan(
                patientId: PatientId::fromInt((int) $request->input('patient_id')),
                doctorId: DoctorId::fromInt((int) $request->input('doctor_id')),
                tenantId: TenantId::fromInt((int) $request->input('tenant_id')),
                name: $request->input('name'),
                description: $request->input('description'),
                toothChartId: $request->input('tooth_chart_id') 
                    ? ToothChartId::fromInt((int) $request->input('tooth_chart_id')) 
                    : null,
            );

            return new JsonResponse([
                'success' => true,
                'message' => 'Treatment plan created successfully',
                'plan' => [
                    'id' => $plan->id->value,
                    'patient_id' => $plan->patientId->value,
                    'doctor_id' => $plan->doctorId->value,
                    'name' => $plan->name,
                    'description' => $plan->description,
                    'status' => $plan->status->value,
                    'total_cost' => $plan->totalCost->toDecimal(),
                ],
            ], 201);
        } catch (ValidationException $e) {
            return new JsonResponse(['error' => 'Validation failed', 'details' => $e->errors()], 422);
        } catch (\Throwable $e) {
            $this->logger->error('Treatment plan creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return new JsonResponse(['error' => 'Internal server error'], 500);
        }
    }

    public function addTreatmentStep(Request $request, int $planId): JsonResponse
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'cost' => 'required|numeric|min:0',
                'description' => 'nullable|string|max:1000',
                'tooth_number' => 'nullable|string|max:10',
                'sort_order' => 'nullable|integer|min:0',
            ]);

            $plan = $this->treatmentPlanService->addStep(
                planId: TreatmentPlanId::fromInt($planId),
                name: $request->input('name'),
                cost: Money::fromFloat((float) $request->input('cost')),
                description: $request->input('description'),
                toothNumber: $request->input('tooth_number'),
                sortOrder: $request->input('sort_order', 0),
            );

            return new JsonResponse([
                'success' => true,
                'message' => 'Treatment step added successfully',
                'plan' => [
                    'id' => $plan->id->value,
                    'total_cost' => $plan->totalCost->toDecimal(),
                    'steps_count' => $plan->steps->count(),
                ],
            ]);
        } catch (ValidationException $e) {
            return new JsonResponse(['error' => 'Validation failed', 'details' => $e->errors()], 422);
        } catch (\Throwable $e) {
            $this->logger->error('Treatment step addition failed', [
                'plan_id' => $planId,
                'error' => $e->getMessage(),
            ]);
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    public function activatePlan(Request $request, int $planId): JsonResponse
    {
        try {
            $plan = $this->treatmentPlanService->activatePlan(
                TreatmentPlanId::fromInt($planId)
            );

            return new JsonResponse([
                'success' => true,
                'message' => 'Treatment plan activated successfully',
                'plan' => [
                    'id' => $plan->id->value,
                    'status' => $plan->status->value,
                ],
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Treatment plan activation failed', [
                'plan_id' => $planId,
                'error' => $e->getMessage(),
            ]);
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    public function scheduleStep(Request $request, int $planId, string $stepId): JsonResponse
    {
        try {
            $request->validate([
                'scheduled_date' => 'required|date|after:today',
            ]);

            $plan = $this->treatmentPlanService->scheduleStep(
                planId: TreatmentPlanId::fromInt($planId),
                stepId: $stepId,
                scheduledDate: new \DateTimeImmutable($request->input('scheduled_date'))
            );

            return new JsonResponse([
                'success' => true,
                'message' => 'Treatment step scheduled successfully',
                'plan' => [
                    'id' => $plan->id->value,
                ],
            ]);
        } catch (ValidationException $e) {
            return new JsonResponse(['error' => 'Validation failed', 'details' => $e->errors()], 422);
        } catch (\Throwable $e) {
            $this->logger->error('Treatment step scheduling failed', [
                'plan_id' => $planId,
                'step_id' => $stepId,
                'error' => $e->getMessage(),
            ]);
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    public function completeStep(Request $request, int $planId, string $stepId): JsonResponse
    {
        try {
            $request->validate([
                'performed_by' => 'required|integer',
            ]);

            $plan = $this->treatmentPlanService->completeStep(
                planId: TreatmentPlanId::fromInt($planId),
                stepId: $stepId,
                performedBy: DoctorId::fromInt((int) $request->input('performed_by'))
            );

            return new JsonResponse([
                'success' => true,
                'message' => 'Treatment step completed successfully',
                'plan' => [
                    'id' => $plan->id->value,
                ],
            ]);
        } catch (ValidationException $e) {
            return new JsonResponse(['error' => 'Validation failed', 'details' => $e->errors()], 422);
        } catch (\Throwable $e) {
            $this->logger->error('Treatment step completion failed', [
                'plan_id' => $planId,
                'step_id' => $stepId,
                'error' => $e->getMessage(),
            ]);
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    public function applyDiscount(Request $request, int $planId): JsonResponse
    {
        try {
            $request->validate([
                'discount_amount' => 'required|numeric|min:0',
            ]);

            $plan = $this->treatmentPlanService->applyDiscount(
                planId: TreatmentPlanId::fromInt($planId),
                discountAmount: Money::fromFloat((float) $request->input('discount_amount'))
            );

            return new JsonResponse([
                'success' => true,
                'message' => 'Discount applied successfully',
                'plan' => [
                    'id' => $plan->id->value,
                    'total_cost' => $plan->totalCost->toDecimal(),
                    'discount_amount' => $plan->discountAmount->toDecimal(),
                ],
            ]);
        } catch (ValidationException $e) {
            return new JsonResponse(['error' => 'Validation failed', 'details' => $e->errors()], 422);
        } catch (\Throwable $e) {
            $this->logger->error('Discount application failed', [
                'plan_id' => $planId,
                'error' => $e->getMessage(),
            ]);
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    public function completePlan(Request $request, int $planId): JsonResponse
    {
        try {
            $plan = $this->treatmentPlanService->completePlan(
                TreatmentPlanId::fromInt($planId)
            );

            return new JsonResponse([
                'success' => true,
                'message' => 'Treatment plan completed successfully',
                'plan' => [
                    'id' => $plan->id->value,
                    'status' => $plan->status->value,
                ],
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Treatment plan completion failed', [
                'plan_id' => $planId,
                'error' => $e->getMessage(),
            ]);
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    public function getPatientPlans(Request $request, int $patientId): JsonResponse
    {
        try {
            $plans = $this->treatmentPlanService->getPlansForPatient(
                PatientId::fromInt($patientId)
            );

            return new JsonResponse([
                'success' => true,
                'plans' => array_map(fn ($p) => [
                    'id' => $p->id->value,
                    'name' => $p->name,
                    'description' => $p->description,
                    'status' => $p->status->value,
                    'total_cost' => $p->totalCost->toDecimal(),
                    'steps_count' => $p->steps->count(),
                ], $plans),
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Patient plans retrieval failed', [
                'patient_id' => $patientId,
                'error' => $e->getMessage(),
            ]);
            return new JsonResponse(['error' => 'Internal server error'], 500);
        }
    }

    public function getPlan(Request $request, int $planId): JsonResponse
    {
        try {
            $plan = $this->treatmentPlanService->getPlanById(
                TreatmentPlanId::fromInt($planId)
            );

            if (!$plan) {
                return new JsonResponse(['error' => 'Treatment plan not found'], 404);
            }

            return new JsonResponse([
                'success' => true,
                'plan' => [
                    'id' => $plan->id->value,
                    'patient_id' => $plan->patientId->value,
                    'doctor_id' => $plan->doctorId->value,
                    'name' => $plan->name,
                    'description' => $plan->description,
                    'status' => $plan->status->value,
                    'total_cost' => $plan->totalCost->toDecimal(),
                    'discount_amount' => $plan->discountAmount->toDecimal(),
                    'steps' => array_map(fn ($s) => [
                        'id' => $s->id->value,
                        'name' => $s->name,
                        'cost' => $s->cost->toDecimal(),
                        'status' => $s->status->value,
                        'tooth_number' => $s->toothNumber,
                    ], $plan->steps->all()),
                ],
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Treatment plan retrieval failed', [
                'plan_id' => $planId,
                'error' => $e->getMessage(),
            ]);
            return new JsonResponse(['error' => 'Internal server error'], 500);
        }
    }
}
