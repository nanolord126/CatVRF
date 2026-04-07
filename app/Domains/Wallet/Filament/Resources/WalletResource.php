<?php

declare(strict_types=1);

namespace App\Domains\Wallet\Filament\Resources;

use App\Domains\Wallet\Models\Wallet;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Filament-ресурс для управления кошельками в Tenant Panel.
 *
 * CANON 2026: tenant-scoped (global scope на модели Wallet),
 * все реальные поля модели, NO facades, NO $this в static.
 */
final class WalletResource extends Resource
{
    protected static ?string $model = Wallet::class;

    protected static ?string $navigationIcon = 'heroicon-o-wallet';

    protected static ?string $navigationGroup = 'Финансы';

    protected static ?string $navigationLabel = 'Кошельки';

    protected static ?string $modelLabel = 'Кошелёк';

    protected static ?string $pluralModelLabel = 'Кошельки';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Основная информация')
                ->schema([
                    Forms\Components\TextInput::make('uuid')
                        ->label('UUID')
                        ->disabled()
                        ->dehydrated(false),

                    Forms\Components\TextInput::make('tenant_id')
                        ->label('Tenant ID')
                        ->required()
                        ->numeric(),

                    Forms\Components\TextInput::make('business_group_id')
                        ->label('Business Group ID')
                        ->numeric()
                        ->nullable(),

                    Forms\Components\TextInput::make('current_balance')
                        ->label('Текущий баланс (копейки)')
                        ->numeric()
                        ->default(0)
                        ->disabled(),

                    Forms\Components\TextInput::make('hold_amount')
                        ->label('Заморожено (копейки)')
                        ->numeric()
                        ->default(0)
                        ->disabled(),

                    Forms\Components\Toggle::make('is_active')
                        ->label('Активен')
                        ->default(true),
                ])
                ->columns(2),

            Forms\Components\Section::make('Метаданные')
                ->schema([
                    Forms\Components\TextInput::make('correlation_id')
                        ->label('Correlation ID')
                        ->disabled()
                        ->dehydrated(false),

                    Forms\Components\KeyValue::make('tags')
                        ->label('Теги'),

                    Forms\Components\KeyValue::make('metadata')
                        ->label('Метаданные'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                Tables\Columns\TextColumn::make('uuid')
                    ->label('UUID')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('tenant_id')
                    ->label('Tenant')
                    ->sortable(),

                Tables\Columns\TextColumn::make('business_group_id')
                    ->label('Business Group')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('current_balance')
                    ->label('Баланс')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('hold_amount')
                    ->label('Заморожено')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Активен')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Активен'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Domains\Wallet\Filament\Resources\WalletResource\Pages\ListWallets::route('/'),
            'create' => \App\Domains\Wallet\Filament\Resources\WalletResource\Pages\CreateWallet::route('/create'),
            'edit' => \App\Domains\Wallet\Filament\Resources\WalletResource\Pages\EditWallet::route('/{record}/edit'),
        ];
    }
}
