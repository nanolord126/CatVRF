<?php

declare(strict_types=1);

namespace App\Domains\Fashion\Livewire;

use Livewire\Component;

/**
 * ProductGallery — Livewire component for fashion product image gallery.
 *
 * Features:
 * - Image carousel with thumbnails
 * - 360° view support
 * - Zoom functionality
 * - Required angles: front, back, side, detail, on_model
 *
 * @version 2026.1
 */
final class ProductGallery extends Component
{
    public array $images = [];

    public int $currentIndex = 0;

    public bool $showZoom = false;

    public string $currentView = 'main';

    public const REQUIRED_ANGLES = ['front', 'back', 'side', 'detail', 'on_model'];

    public function mount(array $images): void
    {
        $this->images = $images;
    }

    public function nextImage(): void
    {
        $this->currentIndex = ($this->currentIndex + 1) % count($this->images);
    }

    public function previousImage(): void
    {
        $this->currentIndex = ($this->currentIndex - 1 + count($this->images)) % count($this->images);
    }

    public function selectImage(int $index): void
    {
        $this->currentIndex = $index;
    }

    public function toggleZoom(): void
    {
        $this->showZoom = !$this->showZoom;
    }

    public function setView(string $view): void
    {
        $this->currentView = $view;
    }

    public function getCurrentImage(): ?string
    {
        return $this->images[$this->currentIndex] ?? null;
    }

    public function hasRequiredAngles(): bool
    {
        return count($this->images) >= count(self::REQUIRED_ANGLES);
    }

    public function getMissingAngles(): array
    {
        // In a real implementation, this would check image metadata for angles
        $presentAngles = count($this->images);
        return array_slice(self::REQUIRED_ANGLES, $presentAngles);
    }

    public function render()
    {
        return view('fashion.livewire.product-gallery');
    }
}
