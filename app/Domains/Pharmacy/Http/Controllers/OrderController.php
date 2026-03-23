<?php declare(strict_types=1);

namespace App\Domains\Pharmacy\Http\Controllers;

use App\Domains\Pharmacy\Services\PharmacyService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Support\Str;

final class OrderController extends Controller
{
    public function __construct(private readonly PharmacyService $service) {}

    public function store(Request $request): JsonResponse
    {
        $cid = (string) Str::uuid();
        try {
            return response()->json(['status' => 'created', 'correlation_id' => $cid]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
