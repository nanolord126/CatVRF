<?php declare(strict_types=1);
use Illuminate\Support\Facades\Route;
Route::middleware(['api'])->prefix('api/v1/kids_play_centers')->group(function() { Route::get('/', fn() => response()->json(['message' => 'kids_play_centers API'])); });
