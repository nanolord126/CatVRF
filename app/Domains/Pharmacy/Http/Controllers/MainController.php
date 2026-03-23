<?php declare(strict_types=1);

namespace App\Domains\Pharmacy\Http\Controllers;

use App\Domains\Pharmacy\Services\PharmacyService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Support\Str;

final class MainController extends Controller
{
    public function __construct(private readonly PharmacyService $service) {}

    public function index(Request $request): JsonResponse
    {
        $correlationId = (string) Str::uuid();
        try {
            $isB2B = $request->has('inn') && $request->has('business_card_id');
            return response()->json(['data' => [], 'b2b' => $isB2B, 'correlation_id' => $correlationId]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
