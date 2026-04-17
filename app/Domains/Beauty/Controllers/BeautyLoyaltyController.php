<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Controllers;

use App\Domains\Beauty\DTOs\BeautyLoyaltyDto;
use App\Domains\Beauty\Requests\BeautyLoyaltyRequest;
use App\Domains\Beauty\Resources\BeautyLoyaltyResource;
use App\Domains\Beauty\Services\BeautyLoyaltyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class BeautyLoyaltyController
{
    public function __construct(
        private BeautyLoyaltyService $loyaltyService,
    ) {}

    public function processAction(BeautyLoyaltyRequest $request): JsonResponse
    {
        $dto = BeautyLoyaltyDto::from($request);

        $result = $this->loyaltyService->processAction($dto);

        return response()->json([
            'success' => true,
            'data' => new BeautyLoyaltyResource($result),
            'correlation_id' => $result['correlation_id'],
        ]);
    }

    public function getStatus(Request $request): JsonResponse
    {
        $userId = (int) $request->input('user_id');
        $status = $this->loyaltyService->getLoyaltyStatus($userId);

        return response()->json([
            'success' => true,
            'data' => $status,
        ]);
    }

    public function generateReferral(Request $request): JsonResponse
    {
        $userId = (int) $request->input('user_id');
        $code = $this->loyaltyService->generateReferralCode($userId);

        return response()->json([
            'success' => true,
            'data' => [
                'referral_code' => $code,
                'share_link' => url('/ref/' . $code),
            ],
        ]);
    }
}
