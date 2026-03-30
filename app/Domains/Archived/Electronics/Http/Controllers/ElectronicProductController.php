<?php declare(strict_types=1);

namespace App\Domains\Archived\Electronics\Http\Controllers;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ElectronicProductController extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(


            private readonly ElectronicService $electronicService,


            private readonly FraudControlService $fraudControlService,


        ) {}


        public function index(Request $request): JsonResponse


        {


            $correlationId = Str::uuid()->toString();


            try {


                $tenantId = auth()->user()?->tenant_id ?? 0;


                $products = ElectronicProduct::where('tenant_id', $tenantId)


                    ->when($request->input('brand'),     fn ($q, $v) => $q->where('brand', $v))


                    ->when($request->input('category'),  fn ($q, $v) => $q->where('category', $v))


                    ->when($request->input('min_price'), fn ($q, $v) => $q->where('price', '>=', (int) $v))


                    ->when($request->input('max_price'), fn ($q, $v) => $q->where('price', '<=', (int) $v))


                    ->orderByDesc('rating')


                    ->paginate(20);


                return response()->json(['success' => true, 'data' => $products, 'correlation_id' => $correlationId]);


            } catch (\Throwable $e) {


                Log::channel('audit')->error('Electronics: index error', ['error' => $e->getMessage(), 'correlation_id' => $correlationId]);


                return response()->json(['success' => false, 'message' => 'Ошибка загрузки', 'correlation_id' => $correlationId], 500);


            }


        }


        public function show(int $id): JsonResponse


        {


            $correlationId = Str::uuid()->toString();


            try {


                $product = ElectronicProduct::findOrFail($id);


                return response()->json(['success' => true, 'data' => $product, 'correlation_id' => $correlationId]);


            } catch (\Throwable $e) {


                return response()->json(['success' => false, 'message' => 'Товар не найден', 'correlation_id' => $correlationId], 404);


            }


        }


        public function compare(Request $request): JsonResponse


        {


            $correlationId = Str::uuid()->toString();


            try {


                $ids = array_filter(array_map('intval', explode(',', $request->input('ids', ''))));


                if (empty($ids) || count($ids) > 4) {


                    return response()->json(['success' => false, 'message' => 'Укажите от 1 до 4 товаров', 'correlation_id' => $correlationId], 422);


                }


                $products = ElectronicProduct::whereIn('id', $ids)->get();


                return response()->json(['success' => true, 'data' => $products, 'correlation_id' => $correlationId]);


            } catch (\Throwable $e) {


                return response()->json(['success' => false, 'message' => 'Ошибка сравнения', 'correlation_id' => $correlationId], 500);


            }


        }


        public function order(Request $request): JsonResponse


        {


            $correlationId = Str::uuid()->toString();


            try {


                $userId = auth()->id();


                $fraudResult = $this->fraudControlService->check(


                    userId: $userId,


                    operationType: 'electronics_order',


                    amount: (int) $request->input('total_kopecks', 0),


                    correlationId: $correlationId,


                );


                if ($fraudResult['decision'] === 'block') {


                    return response()->json(['success' => false, 'message' => 'Операция заблокирована', 'correlation_id' => $correlationId], 403);


                }


                $validated = $request->validate([


                    'product_id'       => 'required|integer|exists:electronic_products,id',


                    'quantity'         => 'required|integer|min:1|max:10',


                    'delivery_address' => 'required|string',


                ]);


                $order = DB::transaction(function () use ($validated, $userId, $correlationId): ElectronicOrder {


                    $product = ElectronicProduct::findOrFail($validated['product_id']);


                    $order   = ElectronicOrder::create([


                        'uuid'             => Str::uuid(),


                        'tenant_id'        => auth()->user()?->tenant_id ?? 0,


                        'client_id'        => $userId,


                        'product_id'       => $validated['product_id'],


                        'quantity'         => $validated['quantity'],


                        'delivery_address' => $validated['delivery_address'],


                        'total_kopecks'    => $product->price * $validated['quantity'],


                        'status'           => 'pending',


                        'correlation_id'   => $correlationId,


                    ]);


                    Log::channel('audit')->info('Electronics: Order created', [


                        'order_id' => $order->id, 'user_id' => $userId, 'correlation_id' => $correlationId,


                    ]);


                    return $order;


                });


                return response()->json(['success' => true, 'data' => $order, 'correlation_id' => $correlationId], 201);


            } catch (\Illuminate\Validation\ValidationException $e) {


                return response()->json(['success' => false, 'errors' => $e->errors(), 'correlation_id' => $correlationId], 422);


            } catch (\Throwable $e) {


                Log::channel('audit')->error('Electronics: order error', ['error' => $e->getMessage(), 'correlation_id' => $correlationId]);


                return response()->json(['success' => false, 'message' => 'Ошибка заказа', 'correlation_id' => $correlationId], 500);


            }


        }


        public function myOrders(): JsonResponse


        {


            $correlationId = Str::uuid()->toString();


            try {


                $orders = ElectronicOrder::where('client_id', auth()->id())


                    ->with('product')


                    ->orderByDesc('created_at')


                    ->paginate(20);


                return response()->json(['success' => true, 'data' => $orders, 'correlation_id' => $correlationId]);


            } catch (\Throwable $e) {


                return response()->json(['success' => false, 'message' => 'Ошибка', 'correlation_id' => $correlationId], 500);


            }


        }
}
