<?php declare(strict_types=1);

namespace App\Livewire\Marketplace\Dental;

use Livewire\Component;
use Illuminate\Log\LogManager;
use Illuminate\Contracts\Auth\Guard;

final class SmileWidget extends Component
{
    public function __construct(
        private readonly LogManager $logger,
        private readonly Guard $guard,
    ) {}

    use WithFileUploads;

        private $photo;
        private ?array $analysis = null;
        private bool $isAnalyzing = false;

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
            return view('livewire.marketplace.dental.smile-widget');
        }
}
