<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2;

use App\Services\Api\ApiResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class MedicalController
{
    public function __construct(
        private readonly ApiResponseService $apiResponse
    ) {}

    public function diagnose(Request $request): JsonResponse
    {
        // TODO: Implement v2 diagnosis with enhanced security
        return $this->apiResponse->success([
            'diagnosis_id' => 'v2-'.uniqid(),
            'version' => '2.0.0',
        ], 'Diagnosis created with v2 API');
    }

    public function showDiagnosis(Request $request, string $id): JsonResponse
    {
        // TODO: Implement v2 diagnosis retrieval
        return $this->apiResponse->success([
            'id' => $id,
            'version' => '2.0.0',
        ], 'Diagnosis retrieved with v2 API');
    }

    public function createAppointment(Request $request): JsonResponse
    {
        // TODO: Implement v2 appointment creation
        return $this->apiResponse->created([
            'appointment_id' => 'v2-'.uniqid(),
            'version' => '2.0.0',
        ], 'Appointment created with v2 API');
    }

    public function listAppointments(Request $request): JsonResponse
    {
        // TODO: Implement v2 appointment listing
        return $this->apiResponse->success([
            'appointments' => [],
            'version' => '2.0.0',
        ], 'Appointments listed with v2 API');
    }
}
