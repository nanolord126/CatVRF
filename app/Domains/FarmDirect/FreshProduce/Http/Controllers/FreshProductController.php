<?php declare(strict_types=1);

namespace App\Domains\FarmDirect\FreshProduce\Http\Controllers;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class FreshProductController extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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
                Log::channel('audit')->error('FreshProduce: index error', ['error' => $e->getMessage(), 'correlation_id' => $correlationId]);
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
                Log::channel('audit')->error('FreshProduce: boxes error', ['error' => $e->getMessage(), 'correlation_id' => $correlationId]);
                return response()->json(['success' => false, 'message' => 'Ошибка загрузки боксов', 'correlation_id' => $correlationId], 500);
            }
        }
}
