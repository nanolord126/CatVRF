<?php

namespace App\Livewire;

use App\Models\BeautyProduct;
use Livewire\Component;

class BeautyShopShowcase extends Component
{
    public function render()
    {
        return view('livewire.beauty-shop-showcase', [
            'products' => BeautyProduct::where('stock', '>', 0)->latest()->take(6)->get()
        ]);
    }
}
