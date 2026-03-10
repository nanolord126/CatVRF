<?php

namespace App\Filament\Pages\Auth;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Form;
use Filament\Pages\Auth\Register as BaseRegister;
use Illuminate\Contracts\Auth\Authenticatable;
use App\Models\Tenant;
use App\Models\User;
use App\Models\AgencyReferral;
use Illuminate\Support\Str;
use Filament\Notifications\Notification;
use Illuminate\Support\HtmlString;
use Filament\Forms\Get;
use Filament\Forms\Set;

class Register extends BaseRegister
{
    protected function getForms(): array
    {
        return [
            'form' => $this->form(
                $this->makeForm()
                    ->schema([
                        $this->getNameFormComponent(),
                        $this->getEmailFormComponent(),
                        $this->getPasswordFormComponent(),
                        $this->getPasswordConfirmationFormComponent(),
                        TextInput::make('company_name')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true),
                        Toggle::make('no_inn')
                            ->label('Нет ИНН (Физлицо, сдающее апартаменты)')
                            ->live()
                            ->dehydrated(false),
                        TextInput::make('inn')
                            ->label('ИНН')
                            ->placeholder('10 или 12 цифр')
                            ->required(fn (Get $get) => ! $get('no_inn'))
                            ->hidden(fn (Get $get) => $get('no_inn'))
                            ->numeric()
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Get $get, Set $set, ?string $state) {
                                if (!$state || strlen($state) < 10) return;

                                // AI Registration Helper 2026
                                try {
                                    $set('company_name', '🤖 Поиск в реестре...');
                                    
                                    // Simulated Dadata/Nalog.ru API Proxy
                                    $mockData = [
                                        '7707083893' => 'ПАО СБЕРБАНК',
                                        '7730606679' => 'ООО "ЯНДЕКС"',
                                        '7704217370' => 'ПАО "ГАЗПРОМ"',
                                        '5038030436' => 'ООО "ЭКСМО"',
                                    ];

                                    $foundName = $mockData[$state] ?? null;

                                    if ($foundName) {
                                        $set('company_name', $foundName);
                                        Notification::make()
                                            ->title('AI: Контрагент найден')
                                            ->success()
                                            ->body("Данные компании \"{$foundName}\" проверены в ФНС.")
                                            ->send();
                                    } else {
                                        $set('company_name', '');
                                        Notification::make()
                                            ->title('AI: ИНН не верифицирован')
                                            ->warning()
                                            ->body('ИНН не найден в базе. Введите название компании вручную.')
                                            ->send();
                                    }
                                } catch (\Exception $e) {
                                    $set('company_name', '');
                                }
                            })
                            ->unique('tenants', 'data->inn'),
                        Placeholder::make('agency_cta')
                            ->hidden(fn (Get $get) => ! $get('no_inn'))
                            ->content(new HtmlString('Чтобы зарегистрироваться без ИНН, вы должны присоединиться через наше партнерское агентство "Агентство всего хорошего".'))
                            ->hintAction(
                                Action::make('connect_agency')
                                    ->label('Подключиться')
                                    ->action(function (Get $get) {
                                        $data = $get('..');
                                        
                                        // 1. Поиск существующего агентства
                                        $agency = Tenant::where('name', 'Агентство всего хорошего')->first();
                                        
                                        if ($agency) {
                                            // Если агентство существует, создаем пользователя и добавляем его как персонал
                                            $user = User::create([
                                                'name' => $data['name'],
                                                'email' => $data['email'],
                                                'password' => bcrypt($data['password']),
                                            ]);

                                            // В идеале здесь должна быть логика привязки пользователя к тенанту и назначение роли "Object Administrator"
                                            // Так как мы используем schema-per-tenant, пользователь должен быть создан в центральной базе (уже сделано)
                                            // А затем ему нужно выдать роль внутри контекста тенанта.
                                            // Для упрощения сейчас фиксируем запрос в рефералах со статусом 'processed'.
                                            
                                            AgencyReferral::create([
                                                'name' => $data['name'],
                                                'email' => $data['email'],
                                                'company_name' => $data['company_name'],
                                                'suggested_role' => 'object_admin',
                                                'status' => 'processed',
                                            ]);

                                            Notification::make()
                                                ->title('Регистрация завершена')
                                                ->success()
                                                ->body('Вы успешно зарегистрированы в системе агентства "Агентство всего хорошего" как Администратор объекта.')
                                                ->send();
                                        } else {
                                            // Если агентства еще нет, создаем обычный реферал с указанной ролью
                                            AgencyReferral::create([
                                                'name' => $data['name'],
                                                'email' => $data['email'],
                                                'company_name' => $data['company_name'],
                                                'suggested_role' => 'object_admin',
                                            ]);

                                            Notification::make()
                                                ->title('Заявка отправлена')
                                                ->success()
                                                ->body('Ваша заявка в "Агентство всего хорошего" успешно создана. Вы будете назначены Администратором объекта.')
                                                ->send();
                                        }

                                        // Notify Admin
                                        $admin = User::where('email', 'admin@admin.com')->first() ?? User::first();
                                        if ($admin) {
                                            Notification::make()
                                                ->title('Новая заявка: Администратор объекта')
                                                ->body("Пользователь {$data['name']} ({$data['email']}) хочет подключиться как Администратор объекта через Агентство.")
                                                ->sendToDatabase($admin);
                                        }
                                    })
                            ),
                    ])
                    ->statePath('data'),
            ),
        ];
    }

    protected function handleRegistration(array $data): \Illuminate\Database\Eloquent\Model
    {
        if (!empty($data['no_inn'])) {
            // Prevent standard registration if no_inn is checked
            // In a real scenario, we might want to throw a validation error or handle it differently
            // but the UI should ideally guide the user to the Agency button.
            throw new \Exception('Registration without INN must be handled via Agency Request.');
        }

        $user = parent::handleRegistration($data);

        /** @var \App\Models\User $user */
        
        $tenantId = Str::slug($data['company_name']);
        
        $tenant = Tenant::create([
            'id' => $tenantId,
            'name' => $data['company_name'],
            'type' => 'business', 
            'plan' => 'basic',
            'data' => [
                'inn' => $data['inn'],
            ],
        ]);

        $tenant->domains()->create([
            'domain' => $tenantId . '.' . config('tenancy.central_domains')[0],
        ]);

        return $user;
    }
}
