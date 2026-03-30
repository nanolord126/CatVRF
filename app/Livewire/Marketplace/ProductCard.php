<?php declare(strict_types=1);

namespace App\Livewire\Marketplace;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ProductCard extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public int $productId;
        public string $productName;
        public int $price;
        public float $rating;
        public string $imageUrl;
        public string $vertical;

        public function mount(int $productId, string $productName, int $price, float $rating, string $imageUrl, string $vertical): void
        {
            $this->productId = $productId;
            $this->productName = $productName;
            $this->price = $price;
            $this->rating = $rating;
            $this->imageUrl = $imageUrl;
            $this->vertical = $vertical;
        }

        public function addToCart(): void
        {
            $cart = session()->get('cart', []);
            $key = "{$this->vertical}-{$this->productId}";

            if (isset($cart[$key])) {
                $cart[$key]['quantity']++;
            } else {
                $cart[$key] = [
                    'product_id' => $this->productId,
                    'name' => $this->productName,
                    'price' => $this->price,
                    'quantity' => 1,
                    'vertical' => $this->vertical,
                ];
            }

            session()->put('cart', $cart);
            $this->dispatch('cart-updated');
        }

        public function render(): View
        {
            return view('livewire.marketplace.product-card');
        }
}
