<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Livewire\Marketplace\Dental\DentalShowcase;

/*
| Публичные маршруты Marketplace - Подсистема DENTAL
*/

Route::middleware(['web'])->group(function () {
    // Главная витрина стоматологии
    Route::get('/dental', DentalShowcase::class)->name('marketplace.dental.index');
    
    // Группа маршрутов внутри /dental может быть расширена (карточка врача и тд)
});
