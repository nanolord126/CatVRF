<?php

declare(strict_types=1);

namespace App\Http\Livewire\Auth;

use Illuminate\Contracts\View\Factory as ViewFactory;

use App\Services\Auth\AuthService;
use Livewire\Component;
use Illuminate\Support\Str;
use Illuminate\View\View;

final class LoginForm extends Component
{
    public string $email = '';

    public string $password = '';

    public bool $remember = false;

    public bool $loading = false;

    public string $errorMessage = '';

    public function __construct(private readonly ViewFactory $viewFactory,
        private readonly AuthService $auth,) {}

    public function login(): mixed
    {
        $this->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $this->loading = true;
        $this->errorMessage = '';

        try {
            $result = $this->auth->login([
                'email' => $this->email,
                'password' => $this->password,
                'device_name' => 'Web Browser',
                'device_type' => 'desktop',
                'fingerprint' => request()->fingerprint(),
                'correlation_id' => Str::uuid()->toString(),
            ]);

            if ($result['requires_2fa'] ?? false) {
                // Redirect to 2FA verification
                return $this->redirectRoute('auth.2fa', ['user_id' => $result['user_id']], navigate: true);
            }

            // Login successful
            $this->dispatch('login-successful');

            return $this->redirectRoute('dashboard', navigate: true);
        } catch (\Throwable $e) {
            $this->errorMessage = $e->getMessage();
        } finally {
            $this->loading = false;
        }
    }

    public function render(): View
    {
        return $this->viewFactory->make('livewire.auth.login-form');
    }
}
