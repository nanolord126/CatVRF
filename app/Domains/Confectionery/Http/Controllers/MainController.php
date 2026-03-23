<?php declare(strict_types=1);

namespace App\Domains\Confectionery\Http\Controllers;

use App\Domains\Confectionery\Services\ConfectioneryService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Support\Str;

final class MainController extends Controller
{
    public function __construct(private readonly ConfectioneryService $service) {}

    public function index(Request $request): JsonResponse
    {
        $cid = (string) Str::uuid();
        try {
            $isB2B = $request->has('inn') && $request->has('business_card_id');
            return response()->json(['data' => [], 'b2b' => $isB2B, 'correlation_id' => $cid]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
