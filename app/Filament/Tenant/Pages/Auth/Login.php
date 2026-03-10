<?php

namespace App\Filament\Tenant\Pages\Auth;

use Filament\Forms\Components\TextInput;
use Filament\Pages\Auth\Login as BaseLogin;
use Filament\Forms\Form;
use Stancl\Tenancy\Facades\Tenancy;

class Login extends BaseLogin
{
    public function mount(): void
    {
        parent::mount();

        // Optional: Pre-fill or display INN if needed context is available
        // In a multi-tenant setup by domain, the tenant is already initialized.
        if (Tenancy::initialized()) {
            $tenant = tenant();
            $inn = $tenant->data['inn'] ?? 'N/A';
            // We can't easily change the static title here without overriding more, 
            // but we can add a notification or a hint to the form.
        }
    }

    public function form(Form $form): Form
    {
        return parent::form($form);
    }
}
