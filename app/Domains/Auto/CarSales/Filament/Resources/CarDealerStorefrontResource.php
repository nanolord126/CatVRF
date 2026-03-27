<?php declare(strict_types=1);

namespace App\Domains\Auto\CarSales\Filament\Resources;

use App\Domains\Auto\CarSales\Models\CarDealerStorefront;
use App\Domains\Auto\CarSales\Filament\Resources\CarDealerStorefrontResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Filament Resource для управления витринами дилеров авто.
 * Production 2026.
 */
final class CarDealerStorefrontResource extends Resource
{
    protected static ?string $model = CarDealerStorefront::class;
    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';
    protected static ?string $navigationGroup = 'Продажи авто';
    protected static ?string $navigationLabel = 'Витрины дилеров';

	public static function form(Form $form): Form
	{
		return $form->schema([
			Forms\Components\Section::make('Основная информация')->schema([
				Forms\Components\TextInput::make('company_name')->label('Название компании')->required()->maxLength(255),
				Forms\Components\TextInput::make('inn')->label('ИНН')->required()->unique(ignoreRecord: true)->maxLength(12),
				Forms\Components\Textarea::make('description')->label('Описание')->rows(3),
			])->columns(2),
			Forms\Components\Section::make('Торговые условия')->schema([
				Forms\Components\TextInput::make('wholesale_discount')->label('Оптовая скидка (%)')->numeric()->minValue(0)->maxValue(100)->suffix('%'),
				Forms\Components\TextInput::make('min_order_amount')->label('Мин. сумма заказа (₽)')->numeric()->default(50000)->prefix('₽'),
			])->columns(2),
			Forms\Components\Section::make('Статус')->schema([
				Forms\Components\Toggle::make('is_verified')->label('Верифицирован')->disabled(),
				Forms\Components\Toggle::make('is_active')->label('Активен')->default(true),
			])->columns(2),
		]);
	}

	public static function table(Table $table): Table
	{
		return $table
			->columns([
				Tables\Columns\TextColumn::make('company_name')->label('Компания')->searchable()->sortable(),
				Tables\Columns\TextColumn::make('inn')->label('ИНН')->searchable(),
				Tables\Columns\TextColumn::make('wholesale_discount')->label('Скидка')->suffix('%')->sortable(),
				Tables\Columns\TextColumn::make('min_order_amount')->label('Мин. заказ')->money('RUB')->sortable(),
				Tables\Columns\IconColumn::make('is_verified')->label('Верифицирован')->boolean(),
				Tables\Columns\IconColumn::make('is_active')->label('Активен')->boolean(),
				Tables\Columns\TextColumn::make('created_at')->label('Создан')->dateTime('d.m.Y')->sortable()->toggleable(isToggledHiddenByDefault: true),
			])
			->filters([
				Tables\Filters\TernaryFilter::make('is_verified')->label('Верифицирован'),
				Tables\Filters\TernaryFilter::make('is_active')->label('Активен'),
			])
			->actions([Tables\Actions\ViewAction::make(), Tables\Actions\EditAction::make()])
			->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
	}

	public static function getPages(): array
	{
		return [
			'index'  => Pages\ListCarDealerStorefronts::route('/'),
			'create' => Pages\CreateCarDealerStorefront::route('/create'),
			'edit'   => Pages\EditCarDealerStorefront::route('/{record}/edit'),
		];
	}
}
