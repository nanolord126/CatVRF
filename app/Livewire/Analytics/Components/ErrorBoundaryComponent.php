<?php

declare(strict_types=1);

namespace App\Livewire\Analytics\Components;

use Illuminate\Contracts\View\Factory as ViewFactory;

use Livewire\Component;

/**
 * Class ErrorBoundaryComponent
 *
 * Livewire component for user cabinet.
 * Personal cabinets use Livewire 3 + Alpine.js + Tailwind 4.
 * Not Filament — Filament is for admin/tenant/B2B panels only.
 */
final class ErrorBoundaryComponent extends Component
{
    private readonly bool $hasError = false;

    private readonly string $errorMessage = '';

    private readonly string $errorCode = '';

    private readonly string $correlationId = '';

    /**
     * Handle setError operation.
     *
     * @throws \DomainException
     */
    public function __construct(
        private readonly ViewFactory $viewFactory,
    ) {}

    public function setError(string $message, string $code = 'ERROR', string $correlationId = ''): void
    {
        $this->hasError = true;
        $this->errorMessage = $message;
        $this->errorCode = $code;
        $this->correlationId = $correlationId;
    }

    /**
     * Handle clearError operation.
     *
     * @throws \DomainException
     */
    public function clearError(): void
    {
        $this->hasError = false;
        $this->errorMessage = '';
        $this->errorCode = '';
        $this->correlationId = '';
    }

    public function retry(): void
    {
        $this->clearError();
        $this->dispatch('error-boundary-retry');
    }

    public function render()
    {
        return $this->viewFactory->make('livewire.analytics.components.error-boundary-component');
    }
}
