<?php declare(strict_types=1);

namespace App\Domains\Pharmacy\Http\Controllers;

use App\Domains\Pharmacy\Services\B2BService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Support\Str;

final class B2BController extends Controller
{
    public function __construct(private readonly B2BService $service) {}

    public function store(Request $request): JsonResponse
    {
        $cid = (string) Str::uuid();
        try {
            $order = $this->service->placeOrder($request->all(), $cid);
            return response()->json(['order' => $order, 'correlation_id' => $cid]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
