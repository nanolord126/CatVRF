<?php
namespace App\Livewire\B2B;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\BusinessGroup;
use App\Services\ImportService;

class BranchImporter extends Component {
    use WithFileUploads;

    public $file;
    public $processing = false;
    public $progress = 0;

    public function startImport(ImportService $service) {
        $this->validate(['file' => 'required|mimes:xlsx,csv|max:10240']);
        $this->processing = true;
        
        $track = $service->submit($this->file, 'branches', auth()->id());
        $this->dispatch('import-started', correlationId: $track->correlation_id);
    }

    public function render() {
        return view('livewire.b2b.branch-importer');
    }
}
