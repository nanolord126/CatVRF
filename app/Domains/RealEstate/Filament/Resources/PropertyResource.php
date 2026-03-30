<?php declare(strict_types=1);

namespace App\Domains\RealEstate\Filament\Resources;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class PropertyResource extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static ?string $model = Property::class;

        protected static ?string $navigationIcon = 'heroicon-o-home';

        protected static ?string $navigationGroup = 'Real Estate';

        protected static ?string $label = 'Объект';

        protected static ?string $pluralLabel = 'Объекты';

        public static function form(Form $form): Form
        {
            return $form
                ->schema([
                    Section::make('Основная информация')
                        ->schema([
                            TextInput::make('address')
                                ->label('Адрес')
                                ->required(),
                            ToggleButtons::make('type')
                                ->label('Тип')
                                ->options([
                                    'apartment' => 'Квартира',
                                    'house' => 'Дом',
                                    'land' => 'Земля',
                                    'commercial' => 'Коммерция',
                                ])->required(),
                            TextInput::make('area')
                                ->label('Площадь (м²)')
                                ->numeric()
                                ->required(),
                            TextInput::make('rooms')
                                ->label('Комнат')
                                ->numeric(),
                            TextInput::make('floor')
                                ->label('Этаж')
                                ->numeric(),
                        ]),
                    Section::make('Параметры')
                        ->schema([
                            TextInput::make('condition')
                                ->label('Состояние')
                                ->default('хорошее'),
                            ToggleButtons::make('status')
                                ->label('Статус')
                                ->options([
                                    'active' => 'Активно',
                                    'sold' => 'Продано',
                                    'rented' => 'Сдано',
                                ]),
                        ]),
                ]);
        }

        public static function table(Table $table): Table
        {
            return $table
                ->columns([
                    TextColumn::make('address')
                        ->label('Адрес')
                        ->searchable()
                        ->sortable(),
                    BadgeColumn::make('type')
                        ->label('Тип')
                        ->colors([
                            'info' => 'apartment',
                            'success' => 'house',
                            'warning' => 'land',
                            'danger' => 'commercial',
                        ]),
                    TextColumn::make('area')
                        ->label('м²')
                        ->sortable(),
                    BadgeColumn::make('status')
                        ->label('Статус')
                        ->colors([
                            'success' => 'active',
                            'danger' => 'sold',
                            'warning' => 'rented',
                        ]),
                    TextColumn::make('created_at')
                        ->label('Создано')
                        ->dateTime()
                        ->sortable(),
                ])
                ->filters([
                    SelectFilter::make('type')
                        ->label('Тип')
                        ->options([
                            'apartment' => 'Квартира',
                            'house' => 'Дом',
                            'land' => 'Земля',
                            'commercial' => 'Коммерция',
                        ]),
                    SelectFilter::make('status')
                        ->label('Статус')
                        ->options([
                            'active' => 'Активно',
                            'sold' => 'Продано',
                            'rented' => 'Сдано',
                        ]),
                ]);
        }

        public static function getPages(): array
        {
            return [
                'index' => \App\Domains\RealEstate\Filament\Resources\PropertyResource\Pages\ListProperties::route('/'),
            ];
        }
}
