<?php declare(strict_types=1);

namespace App\Domains\ShortTermRentals\Http\Controllers;

use App\Domains\ShortTermRentals\Services\ApartmentService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Services\FraudControlService;

final class MainController extends Controller
{
    public function __construct(
        private readonly ApartmentService $apartmentService,
        private readonly FraudControlService $fraudControl
    ) {}

    public function index(Request $request): JsonResponse
    {
        $correlationId = (string) Str::uuid();
        
        try {
            $isB2B = $request->has('inn') && $request->has('business_card_id');
            $this->fraudControl->check($request->all(), 'index_apartments');
            
            $apartments = $this->apartmentService->getActiveApartments(['is_b2b' => $isB2B]);
            
            return response()->json([
                'success' => true,
                'data' => $apartments,
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
