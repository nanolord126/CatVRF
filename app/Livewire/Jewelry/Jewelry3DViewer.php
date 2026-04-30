<?php

declare(strict_types=1);

namespace App\Livewire\Jewelry;

use Illuminate\Contracts\View\Factory as ViewFactory;

use Illuminate\View\View;
use Livewire\Component;

final class Jewelry3DViewer extends Component
{
    private readonly int $modelId;

    private readonly string $modelUrl = '';

    private readonly string $textureUrl = '';

    private readonly float $rotationX = 0;

    private readonly float $rotationY = 0;

    private readonly float $rotationZ = 0;

    private readonly float $zoom = 1.0;

    private readonly string $materialType = 'gold';

    private readonly bool $arMode = false;

    private readonly bool $vrMode = false;

    public function __construct(
        private readonly ViewFactory $viewFactory,
    ) {}

    public function mount(int $modelId): void
    {
        $this->modelId = $modelId;
        $this->loadModel();
    }

    public function loadModel(): void
    {
        // In real app, fetch from database
        $this->modelUrl = 'https://example.com/models/jewelry.glb';
        $this->textureUrl = 'https://example.com/textures/gold.png';
        $this->materialType = 'gold';
    }

    public function rotateX(float $angle): void
    {
        $this->rotationX = $angle % 360;
    }

    public function rotateY(float $angle): void
    {
        $this->rotationY = $angle % 360;
    }

    public function rotateZ(float $angle): void
    {
        $this->rotationZ = $angle % 360;
    }

    public function setZoom(float $level): void
    {
        $this->zoom = max(0.1, min(10, $level));
    }

    public function changeMaterial(string $material): void
    {
        $this->materialType = $material;
        $this->dispatch('material-changed', material: $material);
    }

    public function enableAR(): void
    {
        $this->arMode = true;
        $this->vrMode = false;
        $this->dispatch('ar-enabled');
    }

    public function enableVR(): void
    {
        $this->vrMode = true;
        $this->arMode = false;
        $this->dispatch('vr-enabled');
    }

    public function downloadModel(string $format = 'glb'): void
    {
        $this->dispatch('download-model', format: $format, modelId: $this->modelId);
    }

    public function shareModel(): void
    {
        $shareUrl = route('jewelry.share', ['model_id' => $this->modelId]);
        $this->dispatch('model-shared', url: $shareUrl);
    }

    public function render(): View
    {
        return $this->viewFactory->make('livewire.jewelry.jewelry-3d-viewer');
    }
}
