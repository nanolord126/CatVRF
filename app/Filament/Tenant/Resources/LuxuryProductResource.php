<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use App\Domains\Luxury\Models\LuxuryProduct;
use App\Domains\Luxury\Models\LuxuryBrand;
use App\Filament\Tenant\Resources\LuxuryProductResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

/**
 * LuxuryProductResource
 * 
 * Filament Resource для управления эксклюзивными товарами.
 * Соблюдает tenant scoping и канон 2026.
 * 
 * @version 1.0.0
 * @author CatVRF
 */
final class LuxuryProductResource extends Resource
{
    protected static ?string $model = LuxuryProduct::class;

    protected static ?string $navigationIcon = 'heroicon-o-sparkles';

    protected static ?string $navigationGroup = 'Luxury & VIP';

    protected static ?string $modelLabel = 'Эксклюзивный товар';

    protected static ?string $pluralModelLabel = 'Эксклюзивные товары';

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
                        Forms\Components\Select::make('brand_id')
                            ->relationship('brand', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->label('Бренд'),
                        Forms\Components\TextInput::make('sku')
                            ->unique(ignoreRecord: true)
                            ->required()
                            ->label('Артикул (SKU)'),
                    ])->columns(2),

                Forms\Components\Section::make('Ценообразование (в копейках)')
                    ->schema([
                        Forms\Components\TextInput::make('price_kopecks')
                            ->numeric()
                            ->required()
                            ->suffix('коп.')
                            ->label('Полная стоимость'),
                        Forms\Components\TextInput::make('min_deposit_kopecks')
                            ->numeric()
                            ->required()
                            ->suffix('коп.')
                            ->label('Минимальный депозит'),
                    ])->columns(2),

                Forms\Components\Section::make('Склад и опции')
                    ->schema([
                        Forms\Components\TextInput::make('current_stock')
                            ->numeric()
                            ->default(1)
                            ->label('В наличии'),
                        Forms\Components\Toggle::make('is_personalized')
                            ->label('Доступна персонализация')
                            ->default(false),
                        Forms\Components\KeyValue::make('specifications')
                            ->label('Характеристики (JSON)'),
                    ]),

                Forms\Components\Textarea::make('description')
                    ->label('Описание')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->label('Товар'),
                Tables\Columns\TextColumn::make('brand.name')
                    ->label('Бренд'),
                Tables\Columns\TextColumn::make('price_kopecks')
                    ->formatStateUsing(fn ($state) => number_format($state / 100, 2) . ' ₽')
                    ->label('Цена'),
                Tables\Columns\TextColumn::make('current_stock')
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'success' : 'danger')
                    ->label('Склад'),
                Tables\Columns\IconColumn::make('is_personalized')
                    ->boolean()
                    ->label('VIP'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('brand')
                    ->relationship('brand', 'name')
                    ->label('Фильтр по бренду'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->before(function () {
                        Log::channel('audit')->info('Attempting to edit Luxury Product', [
                            'user_id' => auth()->id(),
                            'correlation_id' => Str::uuid()->toString(),
                        ]);
                    }),
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLuxuryProducts::route('/'),
            'create' => Pages\CreateLuxuryProduct::route('/create'),
            'edit' => Pages\EditLuxuryProduct::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        // Канон 2026: Всегда tenant scoping в Filament
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                // Если нужно обойти какие-то дефолт затычки, но tenant scope должен работать
            ]);
    }
}
