<?php declare(strict_types=1);

namespace App\Livewire\Shared;

use Illuminate\View\View;
use Livewire\Component;
use Illuminate\Auth\AuthManager;
use App\Services\AI\AIConstructorService;
use Illuminate\Http\Request;

/**
 * AIConstructorButton — единая точка входа в AI-конструктор.
 * Рендерит кнопку «AI-конструктор», которая открывает Vue-wizard
 * для указанной вертикали. Поддерживает B2C и B2B режимы.
 *
 * @see resources/views/livewire/shared/ai-constructor-button.blade.php
 */
final class AIConstructorButton extends Component
{
    public string $vertical     = 'marketplace';
    public string $label        = 'AI-Конструктор';
    public bool   $isB2B        = false;
    public bool   $canUse       = false;
    public string $correlationId = '';

    public function __construct(
        private readonly AuthManager        $auth,
        private readonly AIConstructorService $aiConstructor,
        private readonly Request            $request,
    ) {}

    public function mount(string $vertical = 'marketplace', string $label = 'AI-Конструктор'): void
    {
        $this->vertical      = $vertical;
        $this->label         = $label;
        $this->correlationId = (string) \Illuminate\Support\Str::uuid();
        $this->isB2B         = $this->request->has('inn') && $this->request->has('business_card_id');

        $user = $this->auth->user();
        // Кнопка доступна авторизованным пользователям
        $this->canUse = (bool) $user;
    }

    /**
     * Запускает AI-конструктор для текущей вертикали.
     * Отправляет событие в Vue-компонент через window.dispatchEvent.
     */
    public function open(): void
    {
        $this->dispatch('ai-constructor-open', [
            'vertical'      => $this->vertical,
            'isB2B'         => $this->isB2B,
            'correlationId' => $this->correlationId,
        ]);
    }

    public function render(): View
    {
        return view('livewire.shared.ai-constructor-button');
    }
}
