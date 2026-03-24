<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use App\Domains\FarmDirect\Models\Farm;
use Filament\Forms\Components\{Section, Grid, TextInput, Textarea, Toggle, FileUpload, Repeater};
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\{TextColumn, BooleanColumn, BadgeColumn};
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;

final class FarmDirectResource extends Resource
{
    protected static ?string $model = Farm::class;
    protected static ?string $navigationGroup = 'Вертикали';
    protected static ?string $navigationLabel = 'Фермерские товары';
    protected static ?string $modelLabel = 'Ферма';
    protected static ?string $pluralModelLabel = 'Фермы';
    protected static ?int $navigationSort = 8;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Основная информация')
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('name')->label('Название фермы')->required()->maxLength(255),
                        TextInput::make('phone')->label('Телефон')->tel()->required(),
                        Textarea::make('description')->label('Описание продукции')->maxLength(1000)->columnSpanFull(),
                        TextInput::make('farm_type')->label('Тип фермы (овощи, молочная, мясо)')->maxLength(100),
                    ]),
                ]),

            Section::make('Адрес и координаты')
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('address')->label('Адрес')->required()->maxLength(500),
                        TextInput::make('latitude')->label('Широта')->numeric(),
                        TextInput::make('longitude')->label('Долгота')->numeric(),
                    ]),
                ]),

            Section::make('Сертификация')
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('certification_number')->label('Номер сертификата')->maxLength(100),
                        Toggle::make('is_verified')->label('Верифицирована'),
                    ]),
                ]),

            Section::make('Параметры доставки')
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('min_order_amount')->label('Мин. сумма заказа (коп.)')->numeric()->default(30000),
                        TextInput::make('commission_percent')->label('Комиссия (%)')->numeric()->default(12),
                    ]),
                ]),

            Section::make('Зоны доставки')
                ->schema([
                    Repeater::make('delivery_zones')
                        ->label('Зоны доставки')
                        ->schema([
                            Grid::make(3)->schema([
                                TextInput::make('zone_name')->label('Название зоны')->required(),
                                TextInput::make('delivery_time_min')->label('Время доставки (мин)')->numeric()->required(),
                                TextInput::make('delivery_fee_kopecks')->label('Стоимость доставки (коп)')->numeric()->required(),
                            ]),
                        ])->columnSpanFull(),
                ]),

            Section::make('График доступности')
                ->schema([
                    Repeater::make('schedule')
                        ->label('График')
                        ->schema([
                            Grid::make(3)->schema([
                                TextInput::make('day')->label('День')->required(),
                                TextInput::make('opens_at')->label('Открывается')->required(),
                                TextInput::make('closes_at')->label('Закрывается')->required(),
                            ]),
                        ])->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Ферма')->searchable()->sortable(),
                TextColumn::make('phone')->label('Телефон')->searchable(),
                TextColumn::make('address')->label('Адрес')->limit(40),
                TextColumn::make('farm_type')->label('Тип'),
                BadgeColumn::make('is_verified')->label('Статус')
                    ->formatStateUsing(fn(bool $state) => $state ? 'Верифицирована' : 'Не верифицирована')
                    ->color(fn(bool $state) => $state ? 'success' : 'warning'),
                TextColumn::make('commission_percent')->label('Комиссия (%)')->sortable(),
                TextColumn::make('created_at')->label('Создано')->dateTime('d.m.Y')->sortable(),
            ])
            ->filters([
                SelectFilter::make('is_verified')->label('Статус')->options([
                    true => 'Верифицирована',
                    false => 'Не верифицирована',
                ]),
            ])
            ->actions([
                // Actions
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
