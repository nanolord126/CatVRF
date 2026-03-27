<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use App\Domains\Food\Grocery\Models\GroceryStore;
use Filament\Forms\Components\{Section, Grid, TextInput, Toggle};
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\{TextColumn, BadgeColumn};
use Filament\Tables\Table;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;

final class GroceryResource extends Resource
{
    protected static ?string $model = GroceryStore::class;
    protected static ?string $navigationGroup = 'Вертикали';
    protected static ?string $navigationLabel = 'Супермаркеты';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Основная информация')
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('name')->label('Название')->required(),
                        TextInput::make('phone')->label('Телефон')->tel()->required(),
                        TextInput::make('address')->label('Адрес')->required(),
                        Toggle::make('is_verified')->label('Верифицирована'),
                    ]),
                ]),

            Section::make('Координаты и параметры')
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('latitude')->label('Широта')->numeric(),
                        TextInput::make('longitude')->label('Долгота')->numeric(),
                        TextInput::make('min_order')->label('Мин. заказ (коп)')->numeric(),
                        TextInput::make('commission_percent')->label('Комиссия (%)')->numeric()->default(14),
                    ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Магазин')->searchable(),
                TextColumn::make('address')->label('Адрес')->limit(40),
                TextColumn::make('phone')->label('Телефон'),
                BadgeColumn::make('is_verified')->label('Статус')
                    ->color(fn(bool $state) => $state ? 'success' : 'warning'),
                TextColumn::make('created_at')->label('Создано')->dateTime('d.m.Y'),
            ])
            ->bulkActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
