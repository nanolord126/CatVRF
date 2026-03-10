<?php

namespace App\Domains\Clinic\Http\Controllers;

use App\Domains\Clinic\Models\MedicalCard;
use App\Domains\Clinic\Policies\MedicalCardPolicy;
use App\Domains\Clinic\Services\ClinicService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ClinicController extends Controller
{
    public function __construct(
        private ClinicService $service,
        private MedicalCardPolicy $policy
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', MedicalCard::class);
        return response()->json(
            MedicalCard::where('tenant_id', tenant()->id)->paginate($request->input('per_page', 15))
        );
    }

    public function show(MedicalCard $card): JsonResponse
    {
        $this->authorize('view', $card);
        return response()->json($card->load(['patient', 'visits']));
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', MedicalCard::class);
        
        $card = $this->service->createCard($request->all());
        return response()->json($card, 201);
    }

    public function update(Request $request, MedicalCard $card): JsonResponse
    {
        $this->authorize('update', $card);
        return response()->json($this->service->updateCard($card, $request->all()));
    }

    public function destroy(MedicalCard $card): JsonResponse
    {
        $this->authorize('delete', $card);
        $this->service->deleteCard($card);
        return response()->json(['message' => 'Card deleted']);
    }
}
