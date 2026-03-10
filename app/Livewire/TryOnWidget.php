<?php
namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Services\AI\AIShapingAdvisor;

class TryOnWidget extends Component {
    use WithFileUploads;

    public $photo;
    public $product_id;
    public $result_url;
    public $loading = false;

    public function mount(int $productId) { $this->product_id = $productId; }

    public function applyTryOn(AIShapingAdvisor $advisor) {
        $this->validate(['photo' => 'image|max:5120']);
        $this->loading = true; $path = $this->photo->store('tryons');
        $this->result_url = $advisor->virtualTryOn($path, $this->product_id)['result_url'];
        $this->loading = false;
    }

    public function render() { return view('livewire.try-on-widget'); }
}
