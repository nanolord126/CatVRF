<?php declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Models\User;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

final class UserManagementResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'Управление';
    protected static ?string $navigationLabel = 'Пользователи';
    protected static ?string $modelLabel = 'Пользователь';
    protected static ?string $pluralModelLabel = 'Пользователи';
    protected static ?int $navigationSort = 20;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Личные данные')
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->label('Имя')
                        ->required()
                        ->maxLength(255)
                        ->columnSpan(1),
                    TextInput::make('email')
                        ->label('Email')
                        ->email()
                        ->required()
                        ->maxLength(255)
                        ->columnSpan(1),
                    TextInput::make('phone')
                        ->label('Телефон')
                        ->tel()
                        ->maxLength(20)
                        ->columnSpan(1),
                    Select::make('role')
                        ->label('Роль')
                        ->options([
                            'admin'    => 'Администратор',
                            'tenant'   => 'Тенант',
                            'customer' => 'Покупатель',
                            'courier'  => 'Курьер',
                        ])
                        ->columnSpan(1),
                ]),
            Section::make('Статус и безопасность')
                ->columns(2)
                ->schema([
                    Toggle::make('is_active')
                        ->label('Активен')
                        ->default(true)
                        ->columnSpan(1),
                    Toggle::make('is_admin')
                        ->label('Администратор')
                        ->default(false)
                        ->columnSpan(1),
                    Toggle::make('two_factor_enabled')
                        ->label('2FA включён')
                        ->disabled()
                        ->columnSpan(1),
                    DateTimePicker::make('email_verified_at')
                        ->label('Email верифицирован')
                        ->columnSpan(1),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable()
                    ->copyable(),
                TextColumn::make('name')
                    ->label('Имя')
                    ->sortable()
                    ->searchable()
                    ->limit(25),
                TextColumn::make('email')
                    ->label('Email')
                    ->sortable()
                    ->searchable()
                    ->copyable(),
                TextColumn::make('phone')
                    ->label('Телефон')
                    ->searchable(),
                TextColumn::make('role')
                    ->label('Роль')
                    ->badge()
                    ->color(fn (string $state): string => match ((string) $state) {
                        'tenant'   => 'primary',
                        'courier'  => 'warning',
                        default    => 'gray',
                    }),
                IconColumn::make('is_active')
                    ->label('Активен')
                    ->boolean(),
                IconColumn::make('two_factor_enabled')
                    ->label('2FA')
                    ->boolean(),
                TextColumn::make('last_login_at')
                    ->label('Последний вход')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Зарегистрирован')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->label('Роль')
                    ->options([
                        'admin'    => 'Администратор',
                        'tenant'   => 'Тенант',
                        'customer' => 'Покупатель',
                        'courier'  => 'Курьер',
                    ]),
                SelectFilter::make('is_active')
                    ->label('Статус')
                    ->options(['1' => 'Активные', '0' => 'Заблокированные']),
            ])
            ->actions([
                ViewAction::make(),
                Action::make('toggleBlock')
                    ->label(fn (User $record): string => $record->is_active ? 'Заблокировать' : 'Разблокировать')
                    ->icon(fn (User $record): string => $record->is_active ? 'heroicon-o-lock-closed' : 'heroicon-o-lock-open')
                    ->color(fn (User $record): string => $record->is_active ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->modalHeading(fn (User $record): string => $record->is_active ? 'Заблокировать пользователя?' : 'Разблокировать пользователя?')
                    ->action(function (User $record): void {
                        $record->update(['is_active' => !$record->is_active]);
                    }),
                Action::make('resetPassword')
                    ->label('Сбросить пароль')
                    ->icon('heroicon-o-key')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(function (User $record): void {
                        $record->update([
                            'password' => bcrypt(\Illuminate\Support\Str::random(16)),
                        ]);
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('block')
                        ->label('Заблокировать выбранных')
                        ->icon('heroicon-o-lock-closed')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(fn (Collection $records) => $records->each->update(['is_active' => false])),
                    BulkAction::make('unblock')
                        ->label('Разблокировать выбранных')
                        ->icon('heroicon-o-lock-open')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(fn (Collection $records) => $records->each->update(['is_active' => true])),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withTrashed();
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Admin\Resources\UserManagementResource\Pages\ListUsers::route('/'),
            'view'  => \App\Filament\Admin\Resources\UserManagementResource\Pages\ViewUser::route('/{record}'),
        ];
    }
}
