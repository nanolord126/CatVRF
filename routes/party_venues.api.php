<?php declare(strict_types=1);
use Illuminate\Support\Facades\Route;
Route::middleware(['api'])->prefix('api/v1/party_venues')->group(function() { Route::get('/', fn() => response()->json(['message' => 'party_venues API'])); });
