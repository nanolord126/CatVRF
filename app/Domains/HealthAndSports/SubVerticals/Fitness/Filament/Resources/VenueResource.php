<?php

declare(strict_types=1);

namespace Modules\Fitness\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Fitness\Infrastructure\Models\VenueModel;

final class VenueResource extends Resource
{
    protected static ?string $model = VenueModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationGroup = 'Fitness CRM';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основная информация')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->label('Название'),
                        Forms\Components\Textarea::make('description')
                            ->label('Описание')
                            ->rows(3),
                        Forms\Components\TextInput::make('address')
                            ->required()
                            ->maxLength(255)
                            ->label('Адрес'),
                        Forms\Components\TextInput::make('city')
                            ->maxLength(100)
                            ->label('Город'),
                        Forms\Components\TextInput::make('phone')
                            ->tel()
                            ->maxLength(20)
                            ->label('Телефон'),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->maxLength(255)
                            ->label('Email'),
                    ]),

                Forms\Components\Section::make('Локация')
                    ->schema([
                        Forms\Components\TextInput::make('latitude')
                            ->numeric()
                            ->default(0)
                            ->label('Широта'),
                        Forms\Components\TextInput::make('longitude')
                            ->numeric()
                            ->default(0)
                            ->label('Долгота'),
                    ]),

                Forms\Components\Section::make('Характеристики')
                    ->schema([
                        Forms\Components\TextInput::make('capacity')
                            ->required()
                            ->numeric()
                            ->default(100)
                            ->label('Вместимость'),
                        Forms\Components\TextInput::make('total_area')
                            ->required()
                            ->numeric()
                            ->default(500)
                            ->label('Общая площадь (м²)'),
                        Forms\Components\KeyValue::make('amenities')
                            ->label('Удобства')
                            ->keyLabel('Удобство')
                            ->valueLabel('Описание')
                            ->addable(true)
                            ->deletable(true),
                        Forms\Components\TextInput::make('opening_hours')
                            ->label('Часы работы')
                            ->placeholder('Пн-Пт: 8:00-22:00, Сб-Вс: 9:00-21:00'),
                    ]),

                Forms\Components\Section::make('Статус')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->default(true)
                            ->label('Активен'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->label('Название'),
                Tables\Columns\TextColumn::make('city')
                    ->searchable()
                    ->label('Город'),
                Tables\Columns\TextColumn::make('address')
                    ->searchable()
                    ->limit(30)
                    ->label('Адрес'),
                Tables\Columns\TextColumn::make('capacity')
                    ->numeric()
                    ->label('Вместимость'),
                Tables\Columns\TextColumn::make('total_area')
                    ->numeric()
                    ->suffix(' м²')
                    ->label('Площадь'),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Активен'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Создан'),
            ])
            ->filters([
                Tables\Filters\Filter::make('is_active')
                    ->query(fn ($query) => $query->where('is_active', true))
                    ->label('Только активные'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \Modules\Fitness\Filament\Resources\VenueResource\Pages\ListVenues::route('/'),
            'create' => \Modules\Fitness\Filament\Resources\VenueResource\Pages\CreateVenue::route('/create'),
            'edit' => \Modules\Fitness\Filament\Resources\VenueResource\Pages\EditVenue::route('/{record}/edit'),
        ];
    }
}
