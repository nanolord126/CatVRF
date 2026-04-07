<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Presentation\B2C\Livewire;

use App\Domains\Beauty\Services\AI\BeautyImageConstructorService;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;
use Psr\Log\LoggerInterface;

/**
 * BeautyAIConstructor — Livewire-компонент для User Cabinet.
 *
 * Позволяет B2C-пользователю загрузить селфи и получить персонализированные
 * рекомендации по образу: причёска, макияж, уход, AR-примерка.
 *
 * CANON 2026: constructor injection, correlation_id, no facades.
 *
 * @package CatVRF\Beauty\Presentation\B2C
 * @version 2026.1
 */
final class BeautyAIConstructor extends Component
{
    use WithFileUploads;

    /** @var \Livewire\TemporaryUploadedFile|null */
    public $photo = null;

    /** @var array<string, mixed> */
    public array $styleProfile = [];

    /** @var array<int, array<string, mixed>> */
    public array $recommendations = [];

    /** @var string|null */
    public ?string $arLink = null;

    /** @var string */
    public string $correlationId = '';

    /** @var bool */
    public bool $isAnalyzing = false;

    /** @var string|null */
    public ?string $errorMessage = null;

    /** @var float */
    public float $confidenceScore = 0.0;

    /**
     * Правила валидации для загрузки фото.
     *
     * @return array<string, string>
     */
    protected function rules(): array
    {
        return [
            'photo' => 'required|image|max:10240',
        ];
    }

    /**
     * Запустить AI-анализ загруженного фото.
     *
     * Вызывает BeautyImageConstructorService через resolve из контейнера,
     * т.к. Livewire не поддерживает constructor injection напрямую.
     */
    public function runConstructor(): void
    {
        $this->validate();

        $this->isAnalyzing = true;
        $this->errorMessage = null;
        $this->correlationId = Str::uuid()->toString();

        try {
            /** @var BeautyImageConstructorService $service */
            $service = app(BeautyImageConstructorService::class);

            /** @var Guard $guard */
            $guard = app(Guard::class);

            $userId = (int) ($guard->id() ?? 0);

            if ($userId === 0) {
                $this->errorMessage = 'Для использования AI-конструктора необходимо авторизоваться.';
                $this->isAnalyzing = false;
                return;
            }

            $result = $service->analyzePhotoAndRecommend(
                photo: $this->photo,
                userId: $userId,
                correlationId: $this->correlationId,
            );

            $this->styleProfile = $result['style_profile'] ?? [];
            $this->recommendations = $result['recommended'] ?? [];
            $this->arLink = $result['ar_link'] ?? null;
            $this->confidenceScore = (float) ($this->styleProfile['confidence_score'] ?? 0.0);

            /** @var LoggerInterface $logger */
            $logger = app(LoggerInterface::class);
            $logger->info('Beauty AI constructor Livewire completed', [
                'user_id'        => $userId,
                'correlation_id' => $this->correlationId,
            ]);

            $this->dispatch('ai-result', result: $result);
        } catch (\Throwable $e) {
            $this->errorMessage = 'Ошибка анализа: ' . $e->getMessage();

            /** @var LoggerInterface $logger */
            $logger = app(LoggerInterface::class);
            $logger->error('Beauty AI constructor Livewire failed', [
                'error'          => $e->getMessage(),
                'correlation_id' => $this->correlationId,
            ]);
        } finally {
            $this->isAnalyzing = false;
        }
    }

    /**
     * Сбросить результаты для нового анализа.
     */
    public function resetAnalysis(): void
    {
        $this->photo = null;
        $this->styleProfile = [];
        $this->recommendations = [];
        $this->arLink = null;
        $this->errorMessage = null;
        $this->confidenceScore = 0.0;
        $this->correlationId = '';
    }

    /**
     * Рендер компонента.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function render(): \Illuminate\Contracts\View\View
    {
        return view('beauty::livewire.ai-constructor', [
            'styleProfile'    => $this->styleProfile,
            'recommendations' => $this->recommendations,
            'arLink'          => $this->arLink,
            'isAnalyzing'     => $this->isAnalyzing,
            'errorMessage'    => $this->errorMessage,
            'confidenceScore' => $this->confidenceScore,
            'correlationId'   => $this->correlationId,
        ]);
    }
}
