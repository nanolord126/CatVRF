<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Wishlist;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PublicWishlistController extends Controller
{
    public function show($slug)
    {
        $wishlist = Wishlist::where('slug', $slug)
            ->where('is_public', true)
            ->with(['items.product'])
            ->firstOrFail();

        return Inertia::render('Public/Wishlist/Show', [
            'wishlist' => [
                'id' => $wishlist->id,
                'title' => $wishlist->title,
                'items' => $wishlist->items->map(fn ($item) => [
                    'id' => $item->id,
                    'product_name' => $item->product->name,
                    'price' => $item->price_at_addition,
                    'collected' => $item->collected_amount,
                    'is_paid' => $item->is_fully_paid,
                ])
            ]
        ]);
    }

    public function pay(Request $request, $slug)
    {
        // Логика анонимной оплаты через СБП/Карту
        // После успешной транзакции обновляется collected_amount
        return response()->json(['message' => 'Переход на шлюз оплаты...']);
    }
}
