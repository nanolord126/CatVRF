<?php declare(strict_types=1);

namespace App\Livewire\Jewelry;

use Livewire\Component;
use Illuminate\View\View;

final class Jewelry3DViewer extends Component
{
    public int $modelId;
    public string $modelUrl = '';
    public string $textureUrl = '';
    public float $rotationX = 0;
    public float $rotationY = 0;
    public float $rotationZ = 0;
    public float $zoom = 1.0;
    public string $materialType = 'gold';
    public bool $arMode = false;
    public bool $vrMode = false;

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
        return view('livewire.jewelry.jewelry-3d-viewer');
    }
}
