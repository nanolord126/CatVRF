<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class StrApartmentResource extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static ?string $model = StrApartment::class;

        protected static ?string $navigationIcon = 'heroicon-o-home-modern';

        protected static ?string $navigationGroup = 'Short-Term Rentals';

        public static function form(Form $form): Form
        {
            return $form
                ->schema([
                    Forms\Components\Section::make('Main Info')
                        ->schema([
                            Forms\Components\Select::make('str_property_id')
                                ->relationship('property', 'name')
                                ->required()
                                ->searchable()
                                ->preload(),
                            Forms\Components\TextInput::make('name')
                                ->required()
                                ->maxLength(255),
                            Forms\Components\TextInput::make('room_number')
                                ->required()
                                ->maxLength(50),
                            Forms\Components\Select::make('type')
                                ->options([
                                    'studio' => 'Студия',
                                    'apartment' => 'Апартаменты',
                                    'room' => 'Комната',
                                    'villa' => 'Вилла',
                                ])
                                ->required(),
                        ])->columns(2),

                    Forms\Components\Section::make('Pricing & Capacity')
                        ->schema([
                            Forms\Components\TextInput::make('base_price')
                                ->numeric()
                                ->prefix('RUB')
                                ->required()
                                ->helperText('Цена в копейках'),
                            Forms\Components\TextInput::make('deposit_amount')
                                ->numeric()
                                ->prefix('RUB')
                                ->required()
                                ->helperText('Залог в копейках'),
                            Forms\Components\TextInput::make('max_guests')
                                ->numeric()
                                ->minValue(1)
                                ->required(),
                            Forms\Components\TextInput::make('total_rooms')
                                ->numeric()
                                ->minValue(1)
                                ->required(),
                        ])->columns(2),

                    Forms\Components\Section::make('Status & Meta')
                        ->schema([
                            Forms\Components\Toggle::make('is_active')
                                ->default(true),
                            Forms\Components\TagsInput::make('tags')
                                ->placeholder('Добавьте теги для аналитики'),
                            Forms\Components\KeyValue::make('metadata')
                                ->helperText('Дополнительные параметры (удобства, правила)'),
                        ])->columns(1),
                ]);

        public static function getPages(): array
        {
            return [
                'index' => Pages\\ListStrApartment::route('/'),
                'create' => Pages\\CreateStrApartment::route('/create'),
                'edit' => Pages\\EditStrApartment::route('/{record}/edit'),
                'view' => Pages\\ViewStrApartment::route('/{record}'),
            ];

        public static function getPages(): array
        {
            return [
                'index' => Pages\\ListStrApartment::route('/'),
                'create' => Pages\\CreateStrApartment::route('/create'),
                'edit' => Pages\\EditStrApartment::route('/{record}/edit'),
                'view' => Pages\\ViewStrApartment::route('/{record}'),
            ];
        }
}
