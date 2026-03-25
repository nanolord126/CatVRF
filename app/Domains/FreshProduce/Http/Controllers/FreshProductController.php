<?php

declare(strict_types=1);

namespace App\Domains\FreshProduce\Http\Controllers;

use App\Domains\FreshProduce\Models\FreshProduct;
use App\Domains\FreshProduce\Models\ProduceBox;
use App\Domains\FreshProduce\Services\FreshProduceService;
use App\Services\FraudControlService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Каталог свежих продуктов и боксов — КАНОН 2026.
 */
final class FreshProductController
{
    public function __construct(
        private readonly FraudControlService $fraudControlService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $correlationId = Str::uuid()->toString();
        try {
            $tenantId = auth()->user()?->tenant_id ?? 0;

            $products = FreshProduct::where('tenant_id', $tenantId)
                ->when($request->input('category'), fn ($q, $v) => $q->where('category', $v))
                ->when($request->input('is_seasonal'), fn ($q) => $q->where('is_seasonal', true))
                ->orderByDesc('rating')
                ->paginate(20);

            return response()->json([
                'success'        => true,
                'data'           => $products,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            $this->log->channel('audit')->error('FreshProduce: index error', ['error' => $e->getMessage(), 'correlation_id' => $correlationId]);
            return response()->json(['success' => false, 'message' => 'Ошибка загрузки', 'correlation_id' => $correlationId], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        $correlationId = Str::uuid()->toString();
        try {
            $product = FreshProduct::findOrFail($id);
            return response()->json(['success' => true, 'data' => $product, 'correlation_id' => $correlationId]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Продукт не найден', 'correlation_id' => $correlationId], 404);
        }
    }

    public function boxes(Request $request): JsonResponse
    {
        $correlationId = Str::uuid()->toString();
        try {
            $tenantId = auth()->user()?->tenant_id ?? 0;

            $boxes = ProduceBox::where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->orderBy('price_kopecks')
                ->paginate(20);

            return response()->json(['success' => true, 'data' => $boxes, 'correlation_id' => $correlationId]);
        } catch (\Throwable $e) {
            $this->log->channel('audit')->error('FreshProduce: boxes error', ['error' => $e->getMessage(), 'correlation_id' => $correlationId]);
            return response()->json(['success' => false, 'message' => 'Ошибка загрузки боксов', 'correlation_id' => $correlationId], 500);
        }
    }
}
