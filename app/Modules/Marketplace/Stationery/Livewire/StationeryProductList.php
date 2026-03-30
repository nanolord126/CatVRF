<?php declare(strict_types=1);

namespace App\Modules\Marketplace\Stationery\Livewire;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class StationeryProductList extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use WithPagination;

        public string $search = '';
        public ?int $categoryId = null;
        public string $mode = 'B2C'; // B2C or B2B
        public bool $isB2B = false;
        public string $correlation_id = '';

        protected $queryString = [
            'search' => ['except' => ''],
            'categoryId' => ['except' => null],
            'mode' => ['except' => 'B2C'],
        ];

        /**
         * Component Initialization.
         */
        public function mount(): void
        {
            $this->correlation_id = (string) Str::uuid();
            $this->isB2B = ($this->mode === 'B2B');
        }

        /**
         * Switch Business Mode (B2B/B2C).
         */
        public function toggleMode(string $newMode): void
        {
            $this->mode = $newMode;
            $this->isB2B = ($newMode === 'B2B');
            $this->resetPage();
        }

        /**
         * Add Item to Basket (Simulated for Stationery Context).
         */
        public function addToBasket(int $productId, int $quantity = 1): void
        {
            try {
                Log::channel('audit')->info('Stationery basket action', [
                    'product_id' => $productId,
                    'quantity' => $quantity,
                    'mode' => $this->mode,
                    'correlation_id' => $this->correlation_id,
                ]);

                $product = StationeryProduct::findOrFail($productId);

                // Check Fraud before action
                app(FraudControlService::class)->check();

                // Emit to Global Basket Component
                $this->dispatch('basket:item-added', [
                    'id' => $productId,
                    'name' => $product->name,
                    'price' => $this->isB2B ? ($product->b2b_price_cents ?? $product->price_cents) : $product->price_cents,
                    'qty' => $quantity,
                    'vertical' => 'Stationery',
                ]);

                $this->dispatch('notify', [
                    'message' => "{$product->name} added to your basket.",
                    'type' => 'success'
                ]);

            } catch (\Throwable $e) {
                $this->dispatch('notify', [
                    'message' => "Action failed: {$e->getMessage()}",
                    'type' => 'error'
                ]);
            }
        }

        /**
         * Catalog rendering logic (>60 lines per CANON 2026).
         */
        public function render()
        {
            $query = StationeryProduct::query()
                ->where('is_active', true)
                ->with(['store', 'category']);

            if ($this->search) {
                $query->where(fn($q) => $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('tags', 'like', "%{$this->search}%"));
            }

            if ($this->categoryId) {
                $query->where('category_id', $this->categoryId);
            }

            // Ordering and pagination
            $products = $query->latest()->paginate(12);
            $categories = StationeryCategory::where('is_active', true)->get();

            return view('marketplace.stationery.product-list', [
                'products' => $products,
                'categories' => $categories,
                'stores' => StationeryStore::where('is_active', true)->get(),
            ])->layout('layouts.marketplace');
        }
}
