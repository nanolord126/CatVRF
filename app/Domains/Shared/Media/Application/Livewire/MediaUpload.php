<?php

declare(strict_types=1);

namespace Modules\Media\Application\Livewire;

use Illuminate\Http\UploadedFile;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;
use Modules\Media\Application\Services\MediaUploadService;
use Modules\Media\Domain\DTOs\UploadMediaDTO;
use Modules\Media\Domain\Entities\MediaFile;
use Modules\Media\Domain\Exceptions\MediaUploadException;
use Modules\Media\Domain\ValueObjects\MediaCollectionType;

final class MediaUpload extends Component
{
    use WithFileUploads;

    public string $modelType;
    public string $modelId;
    public string $collection = 'images';
    public bool $multiple = false;
    public int $maxFiles = 10;
    public bool $optimize = true;
    public bool $autoUpload = false;
    public array $uploadedFiles = [];
    public array $uploadingFiles = [];
    public array $errors = [];
    public int $uploadProgress = 0;
    public bool $isUploading = false;

    protected $listeners = ['refresh-uploads' => '$refresh'];

    public function upload(): void
    {
        $this->validate([
            'files' => $this->multiple
                ? 'required|array|min:1|max:' . $this->maxFiles
                : 'required|file',
            'files.*' => 'file|max:102400', // 100MB
        ]);

        $this->isUploading = true;
        $this->errors = [];
        $this->uploadProgress = 0;

        try {
            $uploadService = app(MediaUploadService::class);

            $dto = new UploadMediaDTO(
                modelType: $this->modelType,
                modelId: $this->modelId,
                collection: MediaCollectionType::from($this->collection),
                optimize: $this->optimize,
                generateConversions: true,
            );

            if ($this->multiple) {
                /** @var array<int, UploadedFile> $files */
                $files = $this->files;
                $mediaFiles = $uploadService->uploadMultiple($files, $dto);

                foreach ($mediaFiles as $mediaFile) {
                    $this->uploadedFiles[] = [
                        'id' => $mediaFile->id,
                        'name' => $mediaFile->originalFileName,
                        'size' => $this->formatSize($mediaFile->sizeBytes),
                        'url' => $uploadService->getPresignedUrl($mediaFile),
                    ];
                }
            } else {
                /** @var UploadedFile $file */
                $file = $this->files;
                $mediaFile = $uploadService->uploadSingle($file, $dto);

                $this->uploadedFiles[] = [
                    'id' => $mediaFile->id,
                    'name' => $mediaFile->originalFileName,
                    'size' => $this->formatSize($mediaFile->sizeBytes),
                    'url' => $uploadService->getPresignedUrl($mediaFile),
                ];
            }

            $this->uploadProgress = 100;
            $this->dispatch('upload-complete', ['files' => $this->uploadedFiles]);
            $this->dispatch('notify', 'Files uploaded successfully');
        } catch (MediaUploadException $e) {
            $this->errors[] = $e->getMessage();
            $this->dispatch('error', $e->getMessage());
        } catch (\Exception $e) {
            $this->errors[] = 'Failed to upload files';
            $this->dispatch('error', 'Failed to upload files');
        } finally {
            $this->isUploading = false;
            $this->files = [];
        }
    }

    public function removeFile(int $index): void
    {
        unset($this->uploadedFiles[$index]);
        $this->uploadedFiles = array_values($this->uploadedFiles);
        $this->dispatch('file-removed', ['index' => $index]);
    }

    public function deleteMedia(string $mediaId): void
    {
        try {
            $uploadService = app(MediaUploadService::class);
            $mediaRepository = app(\Modules\Media\Domain\Repositories\MediaRepositoryInterface::class);
            $mediaFile = $mediaRepository->findById($mediaId);

            if ($mediaFile !== null) {
                $cdnService = app(\Modules\Media\Application\Services\CdnMediaService::class);
                $cdnService->deleteFromCdn($mediaFile);
                $mediaRepository->delete($mediaId);
            }

            $this->uploadedFiles = array_filter(
                $this->uploadedFiles,
                fn ($file) => $file['id'] !== $mediaId
            );

            $this->dispatch('notify', 'File deleted successfully');
        } catch (\Exception $e) {
            $this->dispatch('error', 'Failed to delete file');
        }
    }

    #[Computed]
    public function acceptedFileTypes(): string
    {
        return match ($this->collection) {
            'avatar', 'images', 'medical', 'before_after', 'portfolio' => 'image/*',
            'video_recording' => 'video/*',
            'documents' => '.pdf,.jpg,.jpeg,.png',
            default => '*',
        };
    }

    #[Computed]
    public function maxFileSize(): int
    {
        return match ($this->collection) {
            'video_recording' => 500 * 1024, // 500MB
            'medical', 'before_after', 'documents' => 15 * 1024, // 15MB
            default => 10 * 1024, // 10MB
        };
    }

    private function formatSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= 1024 ** $pow;

        return round($bytes, 2) . ' ' . $units[$pow];
    }

    public function render(): \Illuminate\View\View
    {
        return view('media::livewire.media-upload');
    }
}
