<?php

declare(strict_types=1);

namespace App\Livewire\Marketplace\Dental;

use DentalSmileConstructorService;

use Illuminate\Contracts\View\Factory as ViewFactory;

use Livewire\Component;
use Illuminate\Log\LogManager;
use Illuminate\Contracts\Auth\Guard;

final class SmileWidget extends Component
{
    use WithFileUploads;

    private $photo;

    private readonly ?array $analysis = null;

    private readonly bool $isAnalyzing = false;

    public function __construct(private readonly DentalSmileConstructorService $dentalSmileConstructorService,
        private readonly ViewFactory $viewFactory,
        private readonly LogManager $logger,
        private readonly Guard $guard,) {}

    public function analyze(): void
    {
        $this->validate([
            'photo' => 'required|image|max:10240',
        ]);

        $this->isAnalyzing = true;

        try {
            $service = $this->dentalSmileConstructorService /* TODO: inject via constructor DI */ /* TODO: inject via DI */;

            // Имитация задержки AI для красоты UI
            sleep(1);

            $this->analysis = $service->analyzeAndRecommend(
                $this->photo,
                $this->guard->id() ?? 0
            );

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'AI анализ завершен!',
            ]);
        } catch (\Throwable $e) {
            $this->logger->channel('audit')->error('Smile Widget AI Analysis failed', ['error' => $e->getMessage()]);
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
        return $this->viewFactory->make('livewire.marketplace.dental.smile-widget');
    }
}
