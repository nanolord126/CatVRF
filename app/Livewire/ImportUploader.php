<?php
namespace App\Livewire;
use Livewire\Component;
use Livewire\WithFileUploads;
use App\Services\ImportService;

class ImportUploader extends Component {
    use WithFileUploads;
    public $importFile;
    public $vertical = 'Hotel';
    public $progress = 0;
    public $trackId = null;

    public function startImport() {
        $service = new ImportService();
        $track = $service->submit($this->importFile, $this->vertical, auth()->id());
        $this->trackId = $track->correlation_id;
        $this->dispatch('import-started', id: $track->correlation_id);
    }
    public function render() {
        return <<<'HTML'
        <div class="p-6 bg-white dark:bg-gray-800 rounded-3xl shadow-3xl border border-gray-100 dark:border-gray-700">
            <h3 class="text-xl font-bold mb-4">🚀 Smart Import 2026</h3>
            <div class="mt-4 flex gap-4">
                <input type="file" wire:model="importFile" class="file:bg-blue-600 file:border-none file:text-white file:rounded-xl file:px-4 file:py-2">
                <button wire:click="startImport" class="px-6 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-xl font-semibold shadow-lg">Process Now</button>
            </div>
            @if($trackId) <livewire:import-progress :correlationId="$trackId" /> @endif
        </div>
        HTML;
    }
}
