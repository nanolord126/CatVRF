<?php

declare(strict_types=1);

namespace Modules\Media\Application\Livewire;

use Illuminate\Http\UploadedFile;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;
use Modules\Media\Application\Services\BulkImportService;
use Modules\Media\Domain\DTOs\BulkImportDTO;
use Modules\Media\Domain\Exceptions\BulkImportException;

final class BulkImport extends Component
{
    use WithFileUploads;

    public string $vertical = 'vetgrooming';
    public ?UploadedFile $zipFile = null;
    public bool $validateOnly = false;
    public bool $dryRun = false;
    public bool $isProcessing = false;
    public int $progress = 0;
    public array $results = [
        'success' => 0,
        'failed' => 0,
        'errors' => [],
    ];
    public array $previewData = [];
    public bool $showPreview = false;

    protected $listeners = ['start-import' => 'import'];

    public function updatedZipFile(): void
    {
        if ($this->zipFile !== null) {
            $this->validateOnly = true;
            $this->preview();
        }
    }

    public function preview(): void
    {
        $this->validate([
            'zipFile' => 'required|file|mimes:zip|max:102400',
            'vertical' => 'required|string|in:vetgrooming,beauty,marketplace',
        ]);

        $this->isProcessing = true;
        $this->progress = 0;

        try {
            $bulkImportService = app(BulkImportService::class);

            // This would parse the ZIP and show preview
            // For now, we'll simulate preview data
            $this->previewData = [
                'file_count' => 0,
                'items' => [],
                'photos' => [],
            ];

            $this->showPreview = true;
            $this->progress = 100;
        } catch (BulkImportException $e) {
            $this->results['errors'][] = $e->getMessage();
            $this->dispatch('error', $e->getMessage());
        } finally {
            $this->isProcessing = false;
        }
    }

    public function import(): void
    {
        $this->validate([
            'vertical' => 'required|string|in:vetgrooming,beauty,marketplace',
            'zipFile' => 'required_without:json|file|mimes:zip|max:102400',
        ]);

        $this->isProcessing = true;
        $this->progress = 0;
        $this->results = ['success' => 0, 'failed' => 0, 'errors' => []];

        try {
            $bulkImportService = app(BulkImportService::class);

            if ($this->zipFile !== null) {
                $this->results = $bulkImportService->importFromZip(
                    $this->zipFile,
                    $this->vertical
                );
            }

            $this->progress = 100;
            $this->dispatch('import-complete', $this->results);

            if ($this->results['failed'] === 0) {
                $this->dispatch('notify', 'Import completed successfully');
            } else {
                $this->dispatch('warning', "Import completed with {$this->results['failed']} errors");
            }
        } catch (BulkImportException $e) {
            $this->results['errors'][] = $e->getMessage();
            $this->dispatch('error', $e->getMessage());
        } finally {
            $this->isProcessing = false;
            $this->zipFile = null;
            $this->showPreview = false;
        }
    }

    public function cancel(): void
    {
        $this->isProcessing = false;
        $this->progress = 0;
        $this->zipFile = null;
        $this->showPreview = false;
        $this->results = ['success' => 0, 'failed' => 0, 'errors' => []];
    }

    #[Computed]
    public function verticals(): array
    {
        return [
            'vetgrooming' => 'Ветеринария и груминг',
            'beauty' => 'Бьюти-салоны',
            'marketplace' => 'Маркетплейс',
        ];
    }

    #[Computed]
    public function canImport(): bool
    {
        return $this->zipFile !== null || !empty($this->previewData);
    }

    #[Computed]
    public function hasErrors(): bool
    {
        return !empty($this->results['errors']);
    }

    public function render(): \Illuminate\View\View
    {
        return view('media::livewire.bulk-import');
    }
}
