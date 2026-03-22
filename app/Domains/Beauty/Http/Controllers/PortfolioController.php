<?php declare(strict_types=1);

namespace App\Domains\Beauty\Http\Controllers;

use App\Domains\Beauty\Services\PortfolioService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Services\FraudControlService;

final class PortfolioController extends Controller
{
    public function __construct(
        private readonly PortfolioService $portfolioService,
        private readonly FraudControlService $fraudControl
    ) {}

    public function index(Request $request): JsonResponse
    {
        $correlationId = (string) Str::uuid();
        
        try {
            $isB2B = $request->has('inn') && $request->has('business_card_id');
            $this->fraudControl->check($request->all(), 'index_portfolio');
            
            $items = $this->portfolioService->getItems(['is_b2b' => $isB2B]);
            
            return response()->json([
                'success' => true,
                'data' => $items,
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
