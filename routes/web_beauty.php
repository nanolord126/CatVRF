<?php declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', static fn () => Inertia::render('Home'));

Route::get('/beauty', static fn () => Inertia::render('Home'));

Route::get('/search', static fn () => Inertia::render('Search'));

Route::get('/orders', static fn () => Inertia::render('Orders'));

Route::get('/profile', static fn () => Inertia::render('Profile'));

Route::get('/categories', static fn () => Inertia::render('Categories'));

Route::get('/category/{slug}', static fn (string $slug) => Inertia::render('Category', ['slug' => $slug]));

Route::get('/booking/{slug}/{itemId}', static fn (string $slug, string $itemId) => Inertia::render('Booking', ['slug' => $slug, 'itemId' => $itemId]));
