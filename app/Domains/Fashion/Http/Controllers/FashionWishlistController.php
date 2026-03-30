<?php declare(strict_types=1);

namespace App\Domains\Fashion\Http\Controllers;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class FashionWishlistController extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function index(): JsonResponse
        {
            try {
                $wishlist = FashionWishlist::where('user_id', auth()->id())
                    ->with('product')
                    ->paginate(20);

                return response()->json(['success' => true, 'data' => $wishlist, 'correlation_id' => Str::uuid()]);
            } catch (\Throwable $e) {
                return response()->json(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
            }
        }

        public function a: JsonResponse
        {
            try {
                $correlationId = Str::uuid()->toString();

                DB::transaction(function () use ($id, $correlationId) {
                    FashionWishlist::create([
                        'uuid' => Str::uuid(),
                        'tenant_id' => tenant('id'),
                        'user_id' => auth()->id(),
                        'product_id' => $id,
                        'color' => request('color'),
                        'size' => request('size'),
                        'correlation_id' => $correlationId,
                    ]);

                    Log::channel('audit')->info('Product added to wishlist', [
                        'product_id' => $id,
                        'user_id' => auth()->id(),
                        'correlation_id' => $correlationId,
                    ]);
                });

                return response()->json(['success' => true, 'data' => null, 'correlation_id' => $correlationId], 201);
            } catch (\Throwable $e) {
                return response()->json(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 400);
            }
        }

        public function remove(int $id): JsonResponse
        {
            try {
                $wishlist = FashionWishlist::where('product_id', $id)
                    ->where('user_id', auth()->id())
                    ->firstOrFail();
                $correlationId = Str::uuid()->toString();

                DB::transaction(function () use ($wishlist, $correlationId) {
                    $wishlist->delete();
                    Log::channel('audit')->info('Product removed from wishlist', [
                        'product_id' => $wishlist->product_id,
                        'user_id' => auth()->id(),
                        'correlation_id' => $correlationId,
                    ]);
                });

                return response()->json(['success' => true, 'data' => null, 'correlation_id' => $correlationId]);
            } catch (\Throwable $e) {
                return response()->json(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
            }
        }
}
