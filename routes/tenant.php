<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

/*
|--------------------------------------------------------------------------
| Tenant Routes
|--------------------------------------------------------------------------
|
| Here you can register the tenant routes for your application.
| These routes are loaded by the TenantRouteServiceProvider.
|
| Feel free to customize them however you want. Good luck!
|
*/

Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function () {
    Route::get('/', function () {
        return 'This is your multi-tenant application. The id of the current tenant is ' . tenant('id');
    });

    // Wishlist Public Routes
    Route::get('/wishlist/{slug}', function ($slug) {
        $wishlist = \App\Models\Wishlist::where('slug', $slug)->where('is_public', true)->firstOrFail();
        return view('wishlist.public', compact('wishlist'));
    })->name('wishlist.public');

    Route::post('/wishlist/pay/{itemId}', function ($itemId) {
        $item = \App\Models\WishlistItem::findOrFail($itemId);
        // Интеграция с платежным шлюзом
        return response()->json(['url' => '/payment/mock?amount=' . ($item->price_at_addition - $item->collected_amount)]);
    })->name('wishlist.pay');
});
