<?php declare(strict_types=1);
use Illuminate\Support\Facades\Route;
Route::middleware(['api'])->prefix('api/v1/dance_studios')->group(function() { Route::get('/', fn() => response()->json(['message' => 'dance_studios API'])); });
