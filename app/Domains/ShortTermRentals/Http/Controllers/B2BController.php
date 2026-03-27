<?php

declare(strict_types=1);


namespace App\Domains\ShortTermRentals\Http\Controllers;

use App\Domains\ShortTermRentals\Services\ApartmentService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Services\FraudControlService;

final /**
 * B2BController
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class B2BController extends Controller
{
    public function __construct(
        private readonly ApartmentService $apartmentService,
        private readonly FraudControlService $fraudControl
    ) {}

    public function manageListings(Request $request): JsonResponse
    {
        $correlationId = (string) Str::uuid();
        
        try {
            $isB2B = $request->has('inn') && $request->has('business_card_id');
            if (!$isB2B) {
                return response()->json(['error' => 'B2B only', 'correlation_id' => $correlationId], 403);
            }
            
            $this->fraudControl->check($request->all(), 'manage_b2b_listings');
            
            $listings = $this->apartmentService->getB2BListings($request->all(), $correlationId);
            
            return response()->json([
                'success' => true,
                'data' => $listings,
                'correlation_id' => $correlationId
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId
            ], 403);
        }
    }
}
