<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use App\Domains\Auto\Models\TaxiDriver;
use App\Filament\Tenant\Resources\TaxiDriverResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class TaxiDriverResource extends Resource
{
    protected static ?string $model = TaxiDriver::class;

    protected static ?string $navigationIcon = 'heroicon-o-identification';

    protected static ?string $navigationGroup = 'Такси и Поездки';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Личные данные')
                    ->description('Информация о водителе')
                    ->schema([
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\Select::make('user_id')
                                ->label('Пользователь')
                                ->relationship('user', 'name')
                                ->required()
                                ->searchable()
                                ->preload(),
                            
                            Forms\Components\TextInput::make('phone')
                                ->label('Телефон')
                                ->tel()
                                ->placeholder('+7 (999) 000-0000'),
                        ]),
                        
                        Forms\Components\TextInput::make('full_name')
                            ->label('Полное имя')
                            ->required()
                            ->maxLength(255),
                        
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('passport_number')
                                ->label('Номер паспорта')
                                ->maxLength(10)
                                ->unique(ignoreRecord: true),
                            
                            Forms\Components\DatePicker::make('passport_issue_date')
                                ->label('Дата выдачи паспорта'),
                        ]),
                    ]),
                
                Forms\Components\Section::make('Водительские права')
                    ->description('Информация о лицензии')
                    ->schema([
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('license_number')
                                ->label('Номер лицензии')
                                ->required()
                                ->unique(ignoreRecord: true),
                            
                            Forms\Components\DatePicker::make('license_issue_date')
                                ->label('Дата выдачи'),
                            
                            Forms\Components\DatePicker::make('license_expiry_date')
                                ->label('Дата истечения'),
                            
                            Forms\Components\Select::make('license_category')
                                ->label('Категория')
                                ->options(['B' => 'B', 'C' => 'C', 'D' => 'D'])
                                ->multiple(),
                        ]),
                    ]),
                
                Forms\Components\Section::make('Автомобиль')
                    ->description('Информация об авто')
                    ->schema([
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('vehicle_brand')
                                ->label('Марка'),
                            
                            Forms\Components\TextInput::make('vehicle_model')
                                ->label('Модель'),
                            
                            Forms\Components\TextInput::make('license_plate')
                                ->label('Номерной знак'),
                            
                            Forms\Components\TextInput::make('vehicle_year')
                                ->label('Год')
                                ->numeric(),
                            
                            Forms\Components\Select::make('vehicle_class')
                                ->label('Класс')
                                ->options(['economy' => 'Эконом', 'comfort' => 'Комфорт', 'business' => 'Бизнес'])
                                ->default('economy'),
                        ]),
                    ]),
                
                Forms\Components\Section::make('Рейтинг и статус')
                    ->description('Оценки и управление')
                    ->schema([
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('rating')
                                ->label('Рейтинг')
                                ->numeric()
                                ->disabled()
                                ->default(5.0),
                            
                            Forms\Components\TextInput::make('review_count')
                                ->label('Отзывов')
                                ->numeric()
                                ->disabled(),
                            
                            Forms\Components\Toggle::make('is_active')
                                ->label('Активен')
                                ->default(true),
                            
                            Forms\Components\Toggle::make('is_verified')
                                ->label('Проверен'),
                        ]),
                    ]),
            ]);

    public static function getPages(): array
    {
        return [
            'index' => Pages\\ListTaxiDriver::route('/'),
            'create' => Pages\\CreateTaxiDriver::route('/create'),
            'edit' => Pages\\EditTaxiDriver::route('/{record}/edit'),
            'view' => Pages\\ViewTaxiDriver::route('/{record}'),
        ];

    public static function getPages(): array
    {
        return [
            'index' => Pages\\ListTaxiDriver::route('/'),
            'create' => Pages\\CreateTaxiDriver::route('/create'),
            'edit' => Pages\\EditTaxiDriver::route('/{record}/edit'),
            'view' => Pages\\ViewTaxiDriver::route('/{record}'),
        ];
    }
}
