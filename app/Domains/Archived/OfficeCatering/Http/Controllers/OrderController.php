<?php declare(strict_types=1);

namespace App\Domains\Archived\OfficeCatering\Http\Controllers;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class OrderController extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(


            private readonly OfficeCateringService $service,


        ) {}


        public function store(CateringOrderStoreRequest $request): JsonResponse


        {


            $correlationId = (string) Str::uuid();


            try {


                $data = $request->validated();


                $order = $this->service->createOrder(


                    companyId: $data['catering_company_id'],


                    menuId: $data['menu_id'],


                    data: [


                        'office_name' => $data['office_name'],


                        'office_address' => $data['office_address'],


                        'delivery_datetime' => $data['delivery_datetime'],


                        'person_count' => $data['person_count'],


                        'special_requests' => $data['special_requests'] ?? null,


                    ],


                    correlationId: $correlationId


                );


                return response()->json([


                    'success' => true,


                    'order_id' => $order->id,


                    'uuid' => $order->uuid,


                    'total_kopecks' => $order->total_kopecks,


                    'person_count' => $order->person_count,


                    'status' => $order->status,


                    'correlation_id' => $correlationId,


                ], 201);


            } catch (\RuntimeException $e) {


                \Log::channel('audit')->error('Catering order store error', [


                    'error' => $e->getMessage(),


                    'user_id' => auth()->id(),


                    'correlation_id' => $correlationId,


                ]);


                $code = (int) $e->getCode();


                return response()->json([


                    'success' => false,


                    'message' => $e->getMessage(),


                    'correlation_id' => $correlationId,


                ], $code ?: 400);


            }


        }


        public function show(int $orderId): JsonResponse


        {


            try {


                $order = CateringOrder::findOrFail($orderId);


                if ($order->client_id !== auth()->id() && !auth()->user()->isCateringOwner($order->catering_company_id)) {


                    return response()->json([


                        'success' => false,


                        'message' => 'Unauthorized',


                    ], 403);


                }


                return response()->json([


                    'success' => true,


                    'order' => [


                        'id' => $order->id,


                        'uuid' => $order->uuid,


                        'status' => $order->status,


                        'total_kopecks' => $order->total_kopecks,


                        'payout_kopecks' => $order->payout_kopecks,


                        'commission_kopecks' => $order->commission_kopecks,


                        'payment_status' => $order->payment_status,


                        'person_count' => $order->person_count,


                        'delivery_datetime' => $order->delivery_datetime?->toIso8601String(),


                        'office_name' => $order->office_name,


                        'office_address' => $order->office_address,


                        'menu_items' => $order->menu_items_json,


                        'special_requests' => $order->special_requests,


                        'created_at' => $order->created_at->toIso8601String(),


                    ],


                ]);


            } catch (\Exception $e) {


                \Log::channel('audit')->error('Catering order show error', [


                    'order_id' => $orderId,


                    'user_id' => auth()->id(),


                    'error' => $e->getMessage(),


                ]);


                return response()->json([


                    'success' => false,


                    'message' => 'Order not found',


                ], 404);


            }


        }


        public function getMenus(int $companyId): JsonResponse


        {


            try {


                $menus = $this->service->getMenus($companyId);


                return response()->json([


                    'success' => true,


                    'menus' => $menus->map(fn($m) => [


                        'id' => $m->id,


                        'name' => $m->name,


                        'price_kopecks' => $m->price_kopecks,


                        'for_person_count' => $m->for_person_count,


                        'description' => $m->description,


                        'items' => $m->items_json,


                    ]),


                ]);


            } catch (\Exception $e) {


                return response()->json([


                    'success' => false,


                    'message' => 'Menus not found',


                ], 404);


            }


        }


        public function cancel(int $orderId): JsonResponse


        {


            $correlationId = (string) Str::uuid();


            try {


                $order = CateringOrder::findOrFail($orderId);


                if ($order->client_id !== auth()->id()) {


                    return response()->json([


                        'success' => false,


                        'message' => 'Unauthorized',


                    ], 403);


                }


                $updated = $this->service->cancelOrder($orderId, $correlationId);


                \Log::channel('audit')->info('Catering order cancelled via API', [


                    'order_id' => $orderId,


                    'user_id' => auth()->id(),


                    'correlation_id' => $correlationId,


                ]);


                return response()->json([


                    'success' => true,


                    'message' => 'Order cancelled',


                    'order' => $updated,


                    'correlation_id' => $correlationId,


                ]);


            } catch (\Exception $e) {


                \Log::channel('audit')->error('Catering order cancellation error', [


                    'order_id' => $orderId,


                    'error' => $e->getMessage(),


                    'correlation_id' => $correlationId,


                ]);


                return response()->json([


                    'success' => false,


                    'message' => $e->getMessage(),


                    'correlation_id' => $correlationId,


                ], 400);


            }


        }


        public function getUserOrders(): JsonResponse


        {


            try {


                $orders = $this->service->getUserOrders(auth()->id());


                return response()->json([


                    'success' => true,


                    'orders' => $orders->map(fn($o) => [


                        'id' => $o->id,


                        'status' => $o->status,


                        'total_kopecks' => $o->total_kopecks,


                        'person_count' => $o->person_count,


                        'delivery_datetime' => $o->delivery_datetime?->toIso8601String(),


                        'created_at' => $o->created_at->toIso8601String(),


                    ]),


                ]);


            } catch (\Exception $e) {


                return response()->json([


                    'success' => false,


                    'message' => 'Orders not found',


                ], 404);


            }


        }
}
