<?php declare(strict_types=1);
use Illuminate\Support\Facades\Route;
Route::middleware(['api'])->prefix('api/v1/short_term_rentals')->group(function() { Route::get('/', fn() => response()->json(['message' => 'short_term_rentals API'])); });
