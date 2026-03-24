<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use App\Domains\Confectionery\Models\ConfectioneryShop;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Tables\Actions\BulkAction;
use Illuminate\Support\Collection;

final class ConfectioneryResource extends Resource
{
    protected static ?string $model = ConfectioneryShop::class;
    protected static ?string $navigationIcon = 'heroicon-o-cake';
    protected static ?string $navigationGroup = 'Food';
    protected static ?string $navigationLabel = 'Кондитерские';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Основная информация')
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('name')->label('Название')->required()->maxLength(255),
                        TextInput::make('owner_id')->label('Владелец')->numeric()->required(),
                    ]),
                    Textarea::make('description')->label('Описание')->rows(3),
                ]),
            Section::make('Адрес и контакты')
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('address')->label('Адрес')->required()->maxLength(500),
                        TextInput::make('phone')->label('Телефон')->tel()->required(),
                        TextInput::make('latitude')->label('Широта')->numeric(),
                        TextInput::make('longitude')->label('Долгота')->numeric(),
                    ]),
                ]),
            Section::make('Сертификация')
                ->schema([
                    TextInput::make('certification_number')->label('Номер сертификата'),
                    FileUpload::make('certification_doc')->label('Документ'),
                    Toggle::make('is_verified')->label('Верифицирована'),
                ]),
            Section::make('Параметры')
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('commission_percent')->label('Комиссия (%)')->numeric()->default(14),
                        TextInput::make('max_daily_orders')->label('Макс заказов в день')->numeric(),
                        TextInput::make('min_order_amount')->label('Мин сумма (руб)')->numeric()->default(500),
                        TextInput::make('delivery_time_minutes')->label('Время доставки (мин)')->numeric(),
                    ]),
                    Repeater::make('schedule')
                        ->label('График')
                        ->schema([
                            Grid::make(3)->schema([
                                TextInput::make('day')->label('День'),
                                TextInput::make('open')->label('Открыто')->type('time'),
                                TextInput::make('close')->label('Закрыто')->type('time'),
                            ])
                        ])->collapsible(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Название')->searchable()->sortable(),
                TextColumn::make('address')->label('Адрес')->searchable(),
                TextColumn::make('phone')->label('Телефон'),
                BadgeColumn::make('is_verified')->label('Статус')
                    ->colors(['success' => true, 'danger' => false])
                    ->formatStateUsing(fn($s) => $s ? 'Верифицирована' : 'Не верифицирована'),
                TextColumn::make('commission_percent')->label('Комиссия')->formatStateUsing(fn($s) => $s . '%'),
                TextColumn::make('min_order_amount')->label('Мин заказ')->formatStateUsing(fn($s) => $s . ' руб'),
                TextColumn::make('created_at')->label('Создана')->dateTime('d.m.Y H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('is_verified')->label('Верификация')
                    ->options(['1' => 'Верифицирована', '0' => 'Не верифицирована']),
            ])
            ->actions([
                \Filament\Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                BulkAction::make('verify')->label('Верифицировать')
                    ->action(fn(Collection $records) => $records->each->update(['is_verified' => true])),
            ])
            ->defaultSort('created_at', 'desc');
    }

    protected function getHeaderActions(): array
    {
        return [\Filament\Actions\CreateAction::make()->label('Добавить')];
    }
}
