<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use App\Domains\MeatShops\Models\MeatShop;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Tables\Actions\BulkAction;
use Illuminate\Support\Collection;

final class MeatShopResource extends Resource
{
    protected static ?string $model = MeatShop::class;
    protected static ?string $navigationIcon = 'heroicon-o-fire';
    protected static ?string $navigationGroup = 'Food';
    protected static ?string $navigationLabel = 'Мясные лавки';
    protected static ?string $pluralModelLabel = 'Мясные лавки';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Основная информация')
                ->description('Основные данные о мясной лавке')
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('name')
                            ->label('Название лавки')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Полное название'),
                        TextInput::make('owner_id')
                            ->label('ID владельца')
                            ->numeric()
                            ->required(),
                    ]),
                    Textarea::make('description')
                        ->label('Описание')
                        ->rows(3)
                        ->maxLength(1000),
                ]),
            Section::make('Адрес и контакты')
                ->description('Местоположение, телефон и геолокация')
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('address')
                            ->label('Адрес')
                            ->required()
                            ->maxLength(500),
                        TextInput::make('phone')
                            ->label('Телефон')
                            ->tel()
                            ->required(),
                        TextInput::make('latitude')
                            ->label('Широта')
                            ->numeric(),
                        TextInput::make('longitude')
                            ->label('Долгота')
                            ->numeric(),
                    ]),
                ]),
            Section::make('Сертификация и качество')
                ->description('Номер сертификации, верификация')
                ->schema([
                    TextInput::make('certification_number')
                        ->label('Номер сертификации')
                        ->required(),
                    FileUpload::make('certification_document')
                        ->label('Документ сертификации')
                        ->disk('public')
                        ->directory('meat-certifications'),
                    Toggle::make('is_verified')
                        ->label('Лавка верифицирована')
                        ->default(false),
                ]),
            Section::make('Параметры доставки и работы')
                ->description('Комиссия, зоны доставки, минимальный заказ')
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('commission_percent')
                            ->label('Комиссия платформы (%)')
                            ->numeric()
                            ->default(14),
                        TextInput::make('min_order_amount')
                            ->label('Минимальная сумма заказа (руб)')
                            ->numeric()
                            ->default(500),
                    ]),
                    Repeater::make('delivery_zones')
                        ->label('Зоны доставки')
                        ->schema([
                            Grid::make(2)->schema([
                                TextInput::make('zone_name')
                                    ->label('Название зоны')
                                    ->required(),
                                TextInput::make('delivery_time_min')
                                    ->label('Время доставки (мин)')
                                    ->numeric()
                                    ->required(),
                                TextInput::make('delivery_fee_kopecks')
                                    ->label('Стоимость доставки (коп)')
                                    ->numeric()
                                    ->required(),
                            ])
                        ])->collapsible(),
                    Repeater::make('schedule')
                        ->label('График работы')
                        ->schema([
                            Grid::make(3)->schema([
                                Select::make('day')
                                    ->label('День')
                                    ->options([
                                        'monday' => 'Пн', 'tuesday' => 'Вт', 'wednesday' => 'Ср',
                                        'thursday' => 'Чт', 'friday' => 'Пт', 'saturday' => 'Сб', 'sunday' => 'Вс'
                                    ])
                                    ->required(),
                                TextInput::make('opens_at')
                                    ->label('Открывается')
                                    ->type('time')
                                    ->required(),
                                TextInput::make('closes_at')
                                    ->label('Закрывается')
                                    ->type('time')
                                    ->required(),
                            ])
                        ])->collapsible(),
                ]),
            Section::make('Управление')
                ->description('Корреляция и метаданные')
                ->schema([
                    TextInput::make('correlation_id')
                        ->label('ID корреляции')
                        ->disabled(),
                    Textarea::make('tags')
                        ->label('Теги (JSON)')
                        ->rows(2),
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
                TextColumn::make('phone')
                    ->label('Телефон')
                    ->icon('heroicon-m-phone'),
                BadgeColumn::make('is_verified')
                    ->label('Статус')
                    ->colors(['success' => true, 'danger' => false])
                    ->formatStateUsing(fn($state) => $state ? 'Верифицирована' : 'Не верифицирована'),
                TextColumn::make('commission_percent')
                    ->label('Комиссия')
                    ->formatStateUsing(fn($state) => $state . '%'),
                TextColumn::make('min_order_amount')
                    ->label('Мин. заказ')
                    ->formatStateUsing(fn($state) => $state . ' руб'),
                TextColumn::make('created_at')
                    ->label('Создана')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('is_verified')
                    ->label('Статус верификации')
                    ->options(['1' => 'Верифицирована', '0' => 'Не верифицирована']),
            ])
            ->actions([
                \Filament\Tables\Actions\EditAction::make(),
                \Filament\Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                BulkAction::make('verify')
                    ->label('Верифицировать')
                    ->icon('heroicon-m-check-badge')
                    ->action(fn(Collection $records) => $records->each->update(['is_verified' => true])),
                \Filament\Tables\Actions\BulkDeleteAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make()
                ->label('Добавить лавку'),
        ];
    }
}
