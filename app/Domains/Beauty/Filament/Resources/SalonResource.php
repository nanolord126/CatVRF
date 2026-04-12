<?php declare(strict_types=1);

namespace App\Domains\Beauty\Filament\Resources;

use App\Domains\Beauty\Models\Salon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * SalonResource — Filament-ресурс для управления салонами красоты.
 * Канон CatVRF 2026 — Tenant Panel.
 *
 * Tenant-scoped: владелец видит только свои салоны.
 * Интеграция с AI-конструктором, Wallet, Inventory.
 */
final class SalonResource extends Resource
{
    protected static ?string $model = Salon::class;

    protected static ?string $navigationIcon = 'heroicon-o-scissors';

    protected static ?string $navigationGroup = 'Beauty';

    protected static ?string $modelLabel = 'Салон';

    protected static ?string $pluralModelLabel = 'Салоны';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Основная информация')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Название')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('phone')
                        ->label('Телефон')
                        ->tel()
                        ->maxLength(20),

                    Forms\Components\TextInput::make('email')
                        ->label('Email')
                        ->email()
                        ->maxLength(255),

                    Forms\Components\Select::make('status')
                        ->label('Статус')
                        ->options([
                            'active'   => 'Активен',
                            'inactive' => 'Неактивен',
                            'pending'  => 'На модерации',
                        ])
                        ->default('active')
                        ->required(),
                ]),

            Forms\Components\Section::make('Адрес и координаты')
                ->columns(3)
                ->schema([
                    Forms\Components\TextInput::make('address')
                        ->label('Адрес')
                        ->required()
                        ->maxLength(500)
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('lat')
                        ->label('Широта')
                        ->numeric()
                        ->required(),

                    Forms\Components\TextInput::make('lon')
                        ->label('Долгота')
                        ->numeric()
                        ->required(),
                ]),

            Forms\Components\Section::make('Дополнительно')
                ->schema([
                    Forms\Components\Textarea::make('description')
                        ->label('Описание')
                        ->maxLength(5000)
                        ->rows(4),

                    Forms\Components\TagsInput::make('tags')
                        ->label('Теги'),
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

                Tables\Columns\TextColumn::make('name')
                    ->label('Название')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('address')
                    ->label('Адрес')
                    ->limit(40)
                    ->searchable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Статус')
                    ->colors([
                        'success' => 'active',
                        'danger'  => 'inactive',
                        'warning' => 'pending',
                    ]),

                Tables\Columns\TextColumn::make('masters_count')
                    ->label('Мастера')
                    ->counts('masters')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        'active'   => 'Активен',
                        'inactive' => 'Неактивен',
                        'pending'  => 'На модерации',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListSalons::route('/'),
            'create' => Pages\CreateSalon::route('/create'),
            'edit'   => Pages\EditSalon::route('/{record}/edit'),
        ];
    }
}
