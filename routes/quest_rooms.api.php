<?php declare(strict_types=1);
use Illuminate\Support\Facades\Route;
Route::middleware(['api'])->prefix('api/v1/quest_rooms')->group(function() { Route::get('/', fn() => response()->json(['message' => 'quest_rooms API'])); });
