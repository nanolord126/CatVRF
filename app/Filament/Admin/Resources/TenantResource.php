<?php declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Models\Tenant;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

final class TenantResource extends Resource
{
    protected static ?string $model = Tenant::class;
    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';
    protected static ?string $navigationGroup = 'Управление';
    protected static ?string $navigationLabel = 'Тенанты';
    protected static ?string $modelLabel = 'Тенант';
    protected static ?string $pluralModelLabel = 'Тенанты';
    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Основная информация')
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->label('Название')
                        ->required()
                        ->maxLength(255)
                        ->columnSpan(1),
                    Select::make('type')
                        ->label('Тип')
                        ->options([
                            'individual' => 'Физлицо',
                            'company'    => 'Компания',
                            'ip'         => 'ИП',
                        ])
                        ->required()
                        ->columnSpan(1),
                    TextInput::make('inn')
                        ->label('ИНН')
                        ->maxLength(12)
                        ->columnSpan(1),
                    TextInput::make('kpp')
                        ->label('КПП')
                        ->maxLength(9)
                        ->columnSpan(1),
                    TextInput::make('ogrn')
                        ->label('ОГРН')
                        ->maxLength(15)
                        ->columnSpan(1),
                    TextInput::make('phone')
                        ->label('Телефон')
                        ->tel()
                        ->columnSpan(1),
                    TextInput::make('email')
                        ->label('Email')
                        ->email()
                        ->columnSpan(1),
                    TextInput::make('timezone')
                        ->label('Часовой пояс')
                        ->default('Europe/Moscow')
                        ->columnSpan(1),
                ]),
            Section::make('Адреса')
                ->collapsed()
                ->columns(1)
                ->schema([
                    TextInput::make('legal_address')->label('Юридический адрес'),
                    TextInput::make('actual_address')->label('Фактический адрес'),
                ]),
            Section::make('Статус')
                ->columns(2)
                ->schema([
                    Toggle::make('is_active')
                        ->label('Активен')
                        ->default(true)
                        ->columnSpan(1),
                    Toggle::make('is_verified')
                        ->label('Верифицирован')
                        ->default(false)
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
                    ->copyable()
                    ->limit(12),
                TextColumn::make('name')
                    ->label('Название')
                    ->sortable()
                    ->searchable()
                    ->limit(30),
                TextColumn::make('type')
                    ->label('Тип')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'ip'         => 'warning',
                        'individual' => 'gray',
                        default      => 'gray',
                    }),
                TextColumn::make('inn')
                    ->label('ИНН')
                    ->searchable()
                    ->copyable(),
                IconColumn::make('is_active')
                    ->label('Активен')
                    ->boolean(),
                IconColumn::make('is_verified')
                    ->label('Верифицирован')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Тип')
                    ->options([
                        'individual' => 'Физлицо',
                        'company'    => 'Компания',
                        'ip'         => 'ИП',
                    ]),
                SelectFilter::make('is_active')
                    ->label('Статус активности')
                    ->options(['1' => 'Активные', '0' => 'Неактивные']),
                SelectFilter::make('is_verified')
                    ->label('Верификация')
                    ->options(['1' => 'Верифицированные', '0' => 'Не верифицированные']),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('toggleActive')
                    ->label(fn (Tenant $record): string => $record->is_active ? 'Деактивировать' : 'Активировать')
                    ->icon(fn (Tenant $record): string => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn (Tenant $record): string => $record->is_active ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->action(fn (Tenant $record) => $record->update(['is_active' => !$record->is_active])),
                Action::make('verify')
                    ->label('Верифицировать')
                    ->icon('heroicon-o-shield-check')
                    ->color('success')
                    ->visible(fn (Tenant $record): bool => !$record->is_verified)
                    ->requiresConfirmation()
                    ->action(fn (Tenant $record) => $record->update(['is_verified' => true])),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('activate')
                        ->label('Активировать выбранные')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(fn (Collection $records) => $records->each->update(['is_active' => true])),
                    BulkAction::make('deactivate')
                        ->label('Деактивировать выбранные')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(fn (Collection $records) => $records->each->update(['is_active' => false])),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Admin\Resources\TenantResource\Pages\ListTenants::route('/'),
            'view'  => \App\Filament\Admin\Resources\TenantResource\Pages\ViewTenant::route('/{record}'),
            'edit'  => \App\Filament\Admin\Resources\TenantResource\Pages\EditTenant::route('/{record}/edit'),
        ];
    }
}
