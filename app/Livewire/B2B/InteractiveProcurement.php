<?php

namespace App\Livewire\B2B;

use Livewire\Component;
use App\Models\B2BProduct;
use App\Models\B2BManufacturer;
use App\Services\B2B\B2BProcurementService;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Stancl\Tenancy\Facades\Tenancy;

class InteractiveProcurement extends Component
{
    public $search = '';
    public $budget = 0;
    public $recommendations = [];
    public $selectedCategories = [];

    protected $listeners = ['budgetUpdated' => 'updateBudget'];

    public function mount()
    {
        $this->budget = Auth::user()->balance ?? 50000;
        $this->loadRecommendations();
    }

    public function updatedSearch()
    {
        $this->loadRecommendations();
    }

    public function loadRecommendations()
    {
        $service = app(B2BProcurementService::class);
        // Simulation of AI filtering based on search and budget
        $this->recommendations = B2BProduct::with('manufacturer')
            ->where(function($query) {
                $query->where('name', 'like', "%{$this->search}%")
                      ->orWhereHas('manufacturer', function($q) {
                          $q->where('name', 'like', "%{$this->search}%");
                      });
            })
            ->where('base_wholesale_price', '<=', $this->budget)
            ->orderBy('is_ai_featured', 'desc')
            ->orderBy('base_wholesale_price', 'asc')
            ->limit(6)
            ->get();
    }

    public function smartPurchase(int $productId)
    {
        $product = B2BProduct::findOrFail($productId);
        $service = app(B2BProcurementService::class);
        
        $tenantId = Tenancy::tenant()->getTenantKey();

        try {
            $order = $service->createBulkOrder(
                $tenantId, 
                $product->manufacturer_id, 
                [['product_id' => $product->id, 'quantity' => 1]]
            );

            Notification::make()
                ->title('Smart Purchase Successful')
                ->body("Order #{$order->id} created automatically.")
                ->success()
                ->send();

            $this->loadRecommendations();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Purchase Failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function render()
    {
        return view('livewire.b2b.interactive-procurement');
    }
}
