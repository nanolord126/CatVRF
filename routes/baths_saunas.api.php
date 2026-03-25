<?php declare(strict_types=1);
use Illuminate\Support\Facades\Route;
Route::middleware(['api'])->prefix('api/v1/baths_saunas')->group(function() { Route::get('/', fn() => response()->json(['message' => 'baths_saunas API'])); });
