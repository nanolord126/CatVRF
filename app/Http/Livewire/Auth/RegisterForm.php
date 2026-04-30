<?php

declare(strict_types=1);

namespace App\Http\Livewire\Auth;

use Illuminate\Contracts\View\Factory as ViewFactory;

use App\Services\Auth\RegistrationService;
use Livewire\Component;
use Illuminate\Support\Str;
use Illuminate\View\View;

final class RegisterForm extends Component
{
    public string $name = '';

    public string $email = '';

    public ?string $phone = '';

    public string $password = '';

    public string $password_confirmation = '';

    public ?string $invite_code = '';

    public bool $loading = false;

    public string $successMessage = '';

    public string $errorMessage = '';

    public function __construct(private readonly ViewFactory $viewFactory,
        private readonly RegistrationService $registration,) {}

    public function register(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20', 'unique:users,phone'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $this->loading = true;
        $this->errorMessage = '';
        $this->successMessage = '';

        try {
            $user = $this->registration->registerUser([
                'name' => $this->name,
                'email' => $this->email,
                'phone' => $this->phone,
                'password' => $this->password,
                'invite_code' => $this->invite_code,
                'correlation_id' => Str::uuid()->toString(),
            ]);

            $this->successMessage = 'Registration successful! Please check your email to verify your account.';

            // Reset form
            $this->reset(['name', 'email', 'phone', 'password', 'password_confirmation', 'invite_code']);

            $this->dispatch('registration-successful');
        } catch (\Throwable $e) {
            $this->errorMessage = $e->getMessage();
        } finally {
            $this->loading = false;
        }
    }

    public function render(): View
    {
        return $this->viewFactory->make('livewire.auth.register-form');
    }
}
