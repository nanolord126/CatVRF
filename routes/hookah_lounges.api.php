<?php declare(strict_types=1);
use Illuminate\Support\Facades\Route;
Route::middleware(['api'])->prefix('api/v1/hookah_lounges')->group(function() { Route::get('/', fn() => response()->json(['message' => 'hookah_lounges API'])); });
