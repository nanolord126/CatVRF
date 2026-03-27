<?php

declare(strict_types=1);

namespace App\Livewire\Marketplace\Dental;

use App\Domains\Dental\Services\DentalSmileConstructorService;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Log;

final class SmileWidget extends Component
{
    use WithFileUploads;

    public $photo;
    public ?array $analysis = null;
    public bool $isAnalyzing = false;

    public function analyze(): void
    {
        $this->validate([
            'photo' => 'required|image|max:10240',
        ]);

        $this->isAnalyzing = true;

        try {
            $service = app(DentalSmileConstructorService::class);
            
            // Имитация задержки AI для красоты UI
            sleep(1);

            $this->analysis = $service->analyzeAndRecommend(
                $this->photo,
                auth()->id() ?? 0
            );

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'AI анализ завершен!',
            ]);
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Smile Widget AI Analysis failed', ['error' => $e->getMessage()]);
            $this->addError('photo', 'Ошибка анализа фото. Попробуйте еще раз.');
        } finally {
            $this->isAnalyzing = false;
        }
    }

    public function resetWidget(): void
    {
        $this->reset(['photo', 'analysis', 'isAnalyzing']);
    }

    public function render()
    {
        return view('livewire.marketplace.dental.smile-widget');
    }
}
