<?php declare(strict_types=1);

namespace App\Livewire\Shared;

use Illuminate\View\View;
use Livewire\Component;
use Livewire\Attributes\On;
use Illuminate\Auth\AuthManager;
use App\Models\FraudNotification;

/**
 * FraudAlertBanner — баннер с предупреждением о фрод-активности.
 * Показывается при наличии непрочитанных Warning/High уведомлений фрода.
 * При Critical уведомлениях — всегда поверх контента.
 *
 * @see resources/views/livewire/shared/fraud-alert-banner.blade.php
 */
final class FraudAlertBanner extends Component
{
    public bool   $visible       = false;
    public string $severity      = 'warning'; // warning|high|critical
    public string $message       = '';
    public string $correlationId = '';

    public function __construct(
        private readonly AuthManager $auth,
    ) {}

    public function mount(): void
    {
        $this->correlationId = (string) \Illuminate\Support\Str::uuid();
        $this->refresh();
    }

    #[On('fraud-alert')]
    public function refresh(): void
    {
        $user = $this->auth->user();
        if (!$user) {
            $this->visible = false;
            return;
        }

        $alert = FraudNotification::where('user_id', $user->id)
            ->whereIn('severity', ['warning', 'high', 'critical'])
            ->where('status', 'sent')
            ->where('is_read', false)
            ->orderByRaw("FIELD(severity, 'critical', 'high', 'warning')")
            ->first();

        if (!$alert) {
            $this->visible = false;
            return;
        }

        $this->visible  = true;
        $this->severity = $alert->severity;
        $this->message  = $alert->message;
    }

    public function dismiss(): void
    {
        $user = $this->auth->user();
        if ($user) {
            FraudNotification::where('user_id', $user->id)
                ->where('severity', $this->severity)
                ->update(['is_read' => true]);
        }
        $this->visible = false;
    }

    public function render(): View
    {
        return view('livewire.shared.fraud-alert-banner');
    }
}
