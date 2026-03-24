<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use App\Domains\OfficeCatering\Models\CateringCompany;
use Filament\Forms\Components\{Section, Grid, TextInput, Textarea, Toggle, FileUpload, Repeater, DateTimePicker};
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\{TextColumn, BooleanColumn, BadgeColumn};
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;

final class OfficeCateringResource extends Resource
{
    protected static ?string $model = CateringCompany::class;
    protected static ?string $navigationGroup = 'Вертикали';
    protected static ?string $navigationLabel = 'Офисный кейтеринг';
    protected static ?string $modelLabel = 'Компания кейтеринга';
    protected static ?string $pluralModelLabel = 'Компании кейтеринга';
    protected static ?int $navigationSort = 7;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Основная информация')
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('name')->label('Название компании')->required()->maxLength(255),
                        TextInput::make('phone')->label('Телефон')->tel()->required(),
                        Textarea::make('description')->label('Описание услуг')->maxLength(1000)->columnSpanFull(),
                        Toggle::make('is_verified')->label('Верифицирована'),
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

            Section::make('Лицензирование')
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('certification_number')->label('Номер сертификата')->maxLength(100),
                        FileUpload::make('certification_doc')->label('Сертификат')->disk('public')->directory('catering-certs'),
                    ]),
                ]),

            Section::make('Параметры доставки')
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('min_person_count')->label('Мин. персон')->numeric()->default(5),
                        TextInput::make('max_person_count')->label('Макс. персон')->numeric()->default(500),
                        TextInput::make('min_order_amount')->label('Мин. сумма (коп.)')->numeric()->default(50000),
                        TextInput::make('commission_percent')->label('Комиссия (%)')->numeric()->default(14),
                    ]),
                ]),

            Section::make('Зоны доставки')
                ->schema([
                    Repeater::make('delivery_zones')
                        ->label('Зоны доставки')
                        ->schema([
                            Grid::make(3)->schema([
                                TextInput::make('zone_name')->label('Название зоны')->required(),
                                TextInput::make('delivery_time_min')->label('Мин. время (мин)')->numeric()->required(),
                                TextInput::make('delivery_fee_kopecks')->label('Комиссия доставки (коп)')->numeric()->required(),
                            ]),
                        ])->columnSpanFull(),
                ]),

            Section::make('График работы')
                ->schema([
                    Repeater::make('schedule')
                        ->label('График')
                        ->schema([
                            Grid::make(3)->schema([
                                TextInput::make('day')->label('День')->required(),
                                TextInput::make('opens_at')->label('Открывается (HH:MM)')->required(),
                                TextInput::make('closes_at')->label('Закрывается (HH:MM)')->required(),
                            ]),
                        ])->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Название')->searchable()->sortable(),
                TextColumn::make('phone')->label('Телефон')->searchable(),
                TextColumn::make('address')->label('Адрес')->limit(40),
                BadgeColumn::make('is_verified')->label('Статус')
                    ->formatStateUsing(fn(bool $state) => $state ? 'Верифицирована' : 'Не верифицирована')
                    ->color(fn(bool $state) => $state ? 'success' : 'warning'),
                TextColumn::make('commission_percent')->label('Комиссия (%)')->sortable(),
                TextColumn::make('min_order_amount')->label('Мин. заказ (коп.)')->numeric(),
                TextColumn::make('created_at')->label('Создано')->dateTime('d.m.Y H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('is_verified')->label('Статус')->options([
                    true => 'Верифицирована',
                    false => 'Не верифицирована',
                ]),
            ])
            ->actions([
                // Actions here
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
