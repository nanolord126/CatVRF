<?php

namespace App\Filament\Tenant\Pages\Auth;

use App\Models\Tenant;
use Filament\Pages\Auth\Register as BaseRegister;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class TenantRegister extends BaseRegister
{
    protected function getForms(): array
    {
        return [
            'form' => $this->makeForm()
                ->schema([
                    $this->getNameFormComponent(),
                    $this->getEmailFormComponent(),
                    $this->getPasswordFormComponent(),
                    $this->getPasswordConfirmationFormComponent(),
                    TextInput::make('company_name')->required()->label('Company Name'),
                ])->statePath('data'),
        ];
    }

    protected function handleRegistration(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            $user = $this->getUserModel()::create($data);
            $user->assignRole('tenant-owner');
            $tenantId = \Illuminate\Support\Str::slug($data['company_name']);
            $tenant = Tenant::create(['id' => $tenantId]);
            $tenant->run(fn() => $user->save()); // Scoping
            return $user;
        });
    }
}
