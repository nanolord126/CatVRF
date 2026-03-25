<?php declare(strict_types=1);
use Illuminate\Support\Facades\Route;
Route::middleware(['api'])->prefix('api/v1/board_games')->group(function() { Route::get('/', fn() => response()->json(['message' => 'board_games API'])); });
