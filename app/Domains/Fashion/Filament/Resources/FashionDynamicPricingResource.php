<?php declare(strict_types=1);

namespace App\Domains\Fashion\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\FashionDynamicPricingResource\Pages;

final class FashionDynamicPricingResource extends Resource
{
    protected static ?string $model = \App\Models\FashionDynamicPricing::class;
    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?string $navigationGroup = 'Fashion AI';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Pricing Information')
                    ->schema([
                        Forms\Components\TextInput::make('base_price')
                            ->numeric()
                            ->prefix('$')
                            ->required(),
                        Forms\Components\TextInput::make('dynamic_price')
                            ->numeric()
                            ->prefix('$')
                            ->required(),
                        Forms\Components\TextInput::make('discount_percent')
                            ->numeric()
                            ->suffix('%')
                            ->maxValue(100)
                            ->required(),
                        Forms\Components\TextInput::make('trend_score')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(1)
                            ->step(0.01)
                            ->required(),
                        Forms\Components\Toggle::make('is_flash_sale')
                            ->label('Flash Sale Active'),
                        Forms\Components\DateTimePicker::make('flash_sale_end_time'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('product.name')
                    ->label('Product')
                    ->searchable(),
                Tables\Columns\TextColumn::make('base_price')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('dynamic_price')
                    ->money('USD')
                    ->sortable()
                    ->color('success'),
                Tables\Columns\TextColumn::make('discount_percent')
                    ->suffix('%')
                    ->sortable(),
                Tables\Columns\TextColumn::make('trend_score')
                    ->suffix('score')
                    ->sortable()
                    ->color(fn ($record): string => $record->trend_score >= 0.85 ? 'success' : 'warning'),
                Tables\Columns\IconColumn::make('is_flash_sale')
                    ->boolean()
                    ->label('Flash Sale'),
                Tables\Columns\TextColumn::make('flash_sale_end_time')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_flash_sale')
                    ->label('Flash Sale Active'),
                Tables\Filters\Filter::make('high_trend')
                    ->query(fn (Builder $query): Builder => $query->where('trend_score', '>=', 0.85)),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListFashionDynamicPricing::route('/'),
            'create' => Pages\CreateFashionDynamicPricing::route('/create'),
            'view' => Pages\ViewFashionDynamicPricing::route('/{record}'),
            'edit' => Pages\EditFashionDynamicPricing::route('/{record}/edit'),
        ];
    }
}
