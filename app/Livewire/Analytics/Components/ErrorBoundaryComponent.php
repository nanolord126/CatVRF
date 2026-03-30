<?php declare(strict_types=1);

namespace App\Livewire\Analytics\Components;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ErrorBoundaryComponent extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public bool $hasError = false;
        public string $errorMessage = '';
        public string $errorCode = '';
        public string $correlationId = '';

        public function setError(string $message, string $code = 'ERROR', string $correlationId = ''): void
        {
            $this->hasError = true;
            $this->errorMessage = $message;
            $this->errorCode = $code;
            $this->correlationId = $correlationId;
        }

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
            return view('livewire.analytics.components.error-boundary-component');
        }
}
