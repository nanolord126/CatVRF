<?php declare(strict_types=1);

namespace App\Domains\Food\Filament\Resources;

use App\Domains\Food\Models\Restaurant;
use App\Domains\Food\Filament\Resources\RestaurantResource\Pages;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;

/**
 * Filament Resource для управления ресторанами.
 * Production 2026.
 */
final class RestaurantResource extends Resource
{
    protected static ?string $model = Restaurant::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $navigationGroup = 'Food & Delivery';

    protected static ?string $label = 'Ресторан';

    protected static ?string $pluralLabel = 'Рестораны';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Основная информация')
                    ->schema([
                        TextInput::make('name')
                            ->label('Название')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('address')
                            ->label('Адрес')
                            ->required(),
                        Textarea::make('description')
                            ->label('Описание')
                            ->maxLength(1000),
                        TextInput::make('phone')
                            ->label('Телефон')
                            ->tel(),
                        TextInput::make('website')
                            ->label('Сайт')
                            ->url(),
                    ]),
                Section::make('Параметры')
                    ->schema([
                        ToggleButtons::make('is_verified')
                            ->label('Верифицирован')
                            ->boolean(),
                        ToggleButtons::make('accepts_delivery')
                            ->label('Принимает доставку')
                            ->boolean(),
                        TextInput::make('rating')
                            ->label('Рейтинг')
                            ->numeric()
                            ->between(0, 5),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Название')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('address')
                    ->label('Адрес')
                    ->searchable(),
                BadgeColumn::make('is_verified')
                    ->label('Статус')
                    ->colors([
                        'success' => true,
                        'danger' => false,
                    ]),
                TextColumn::make('rating')
                    ->label('Рейтинг')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Создано')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('is_verified')
                    ->label('Верификация')
                    ->options([
                        true => 'Верифицирован',
                        false => 'Не верифицирован',
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRestaurants::route('/'),
            'create' => Pages\CreateRestaurant::route('/create'),
            'edit' => Pages\EditRestaurant::route('/{record}/edit'),
        ];
    }
}
