<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use App\Filament\Tenant\Resources\UserCrmResource\Pages;
use App\Filament\Tenant\Resources\UserCrmResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

/**
 * CRM клиентов арендатора (КАНОН 2026).
 * Показывает всех клиентов текущего tenant с историей заказов,
 * балансом кошелька и суммой трат.
 */
final class UserCrmResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Клиенты (CRM)';

    protected static ?string $modelLabel = 'Клиент';

    protected static ?string $pluralModelLabel = 'Клиенты';

    protected static ?string $navigationGroup = 'CRM';

    protected static ?int $navigationSort = 1;

    // -------------------------------------------------------------------------
    // FORM
    // -------------------------------------------------------------------------

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Forms\Components\Section::make('Основная информация')->schema([
                Forms\Components\Grid::make(2)->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Имя')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('email')
                        ->label('Email')
                        ->email()
                        ->required()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('phone')
                        ->label('Телефон')
                        ->tel()
                        ->maxLength(20),

                    Forms\Components\DateTimePicker::make('created_at')
                        ->label('Дата регистрации')
                        ->disabled(),
                ]),
            ]),
        ]);

    public static function getPages(): array
    {
        return [
            'index' => Pages\\ListUserCrm::route('/'),
            'create' => Pages\\CreateUserCrm::route('/create'),
            'edit' => Pages\\EditUserCrm::route('/{record}/edit'),
            'view' => Pages\\ViewUserCrm::route('/{record}'),
        ];

    public static function getPages(): array
    {
        return [
            'index' => Pages\\ListUserCrm::route('/'),
            'create' => Pages\\CreateUserCrm::route('/create'),
            'edit' => Pages\\EditUserCrm::route('/{record}/edit'),
            'view' => Pages\\ViewUserCrm::route('/{record}'),
        ];
    }
}
