<?php

namespace App\Filament\Tenant\Resources\Marketplace;

use App\Filament\Tenant\Resources\Marketplace\RestaurantTableResource\Pages;
use App\Models\Tenants\RestaurantTable;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class RestaurantTableResource extends Resource
{
    protected static ?string $model = RestaurantTable::class;
    protected static ?string $navigationGroup = '🍽️ Restaurant Management';
    protected static ?string $navigationIcon = 'heroicon-o-square-3-stack-3d';
    protected static ?string $modelLabel = 'Стол в зале';
    protected static ?string $pluralModelLabel = 'Зал и столы';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Информация о столе')
                    ->schema([
                        Forms\Components\TextInput::make('number')
                            ->label('Номер стола')
                            ->required(),
                        Forms\Components\TextInput::make('capacity')
                            ->label('Вместимость (чел.)')
                            ->numeric()
                            ->required(),
                        Forms\Components\Select::make('status')
                            ->label('Статус')
                            ->options([
                                'available' => 'Свободен',
                                'occupied' => 'Занят',
                                'reserved' => 'Забронирован',
                                'cleaning' => 'Уборка',
                            ])->required(),
                        Forms\Components\TextInput::make('qr_code')
                            ->label('QR-код стола')
                            ->disabled()
                            ->helperText('Генерируется автоматически'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('number')
                    ->label('Стол')
                    ->sortable(),
                Tables\Columns\TextColumn::make('capacity')
                    ->label('Места')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'available' => 'success',
                        'occupied' => 'danger',
                        'reserved' => 'warning',
                        'cleaning' => 'info',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'available' => 'Свободен',
                        'occupied' => 'Занят',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('fire_to_kitchen')
                    ->label('На кухню')
                    ->icon('heroicon-o-fire')
                    ->color('warning')
                    ->hidden(fn ($record) => $record->status !== 'occupied')
                    ->action(fn () => null),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRestaurantTables::route('/'),
            'create' => Pages\CreateRestaurantTable::route('/create'),
            'edit' => Pages\EditRestaurantTable::route('/{record}/edit'),
        ];
    }
}
