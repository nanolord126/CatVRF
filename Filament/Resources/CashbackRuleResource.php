<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Domains\Shared\Loyalty\Models\CashbackRule;
use App\Filament\Resources\CashbackRuleResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

final class CashbackRuleResource extends Resource
{
    protected static ?string $model = CashbackRule::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $navigationGroup = 'Loyalty';

    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Cashback Configuration')
                    ->schema([
                        Forms\Components\Select::make('tenant_id')
                            ->relationship('tenant', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->label('Seller / Shop'),
                        Forms\Components\TextInput::make('percent')
                            ->numeric()
                            ->suffix('%')
                            ->required()
                            ->minValue(0.1)
                            ->maxValue(30)
                            ->default(5)
                            ->helperText('Cashback percentage (0.1% - 30%)'),
                        Forms\Components\TextInput::make('min_order_amount')
                            ->numeric()
                            ->prefix('₽')
                            ->default(1000)
                            ->helperText('Minimum order amount to qualify for cashback (minimum 1000₽)'),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Targeting')
                    ->schema([
                        Forms\Components\Select::make('vertical')
                            ->options([
                                'supermarket' => 'Supermarket',
                            ])
                            ->default('supermarket')
                            ->required()
                            ->disabled(),
                        Forms\Components\Select::make('sub_vertical')
                            ->options([
                                'meat_shops' => 'Мясные лавки',
                                'farm_direct' => 'Farm Direct',
                                'vegan_products' => 'Vegan Products',
                                'confectionery' => 'Кондитерка',
                                'grocery_and_delivery' => 'Grocery & Delivery',
                                'food' => 'Food',
                                'office_catering' => 'Office Catering',
                            ])
                            ->label('Sub-vertical')
                            ->nullable()
                            ->helperText('Leave empty to apply to all sub-verticals'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Status')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->helperText('Enable this cashback rule'),
                    ]),

                Forms\Components\Section::make('Advanced Conditions')
                    ->schema([
                        Forms\Components\KeyValue::make('conditions')
                            ->label('Conditions')
                            ->keyLabel('Condition')
                            ->valueLabel('Value')
                            ->addable()
                            ->editable()
                            ->deletable()
                            ->helperText('JSON conditions for advanced targeting (e.g., categories, products)'),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('tenant.name')
                    ->label('Seller / Shop')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('percent')
                    ->label('Cashback %')
                    ->suffix('%')
                    ->sortable(),
                Tables\Columns\TextColumn::make('min_order_amount')
                    ->label('Min Order')
                    ->money('RUB')
                    ->sortable(),
                Tables\Columns\TextColumn::make('sub_vertical')
                    ->label('Sub-vertical')
                    ->badge()
                    ->color('info')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tenant_id')
                    ->relationship('tenant', 'name')
                    ->label('Seller'),
                Tables\Filters\SelectFilter::make('sub_vertical')
                    ->options([
                        'meat_shops' => 'Мясные лавки',
                        'farm_direct' => 'Farm Direct',
                        'vegan_products' => 'Vegan Products',
                        'confectionery' => 'Кондитерка',
                        'grocery_and_delivery' => 'Grocery & Delivery',
                        'food' => 'Food',
                        'office_catering' => 'Office Catering',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCashbackRules::route('/'),
            'create' => Pages\CreateCashbackRule::route('/create'),
            'view' => Pages\ViewCashbackRule::route('/{record}'),
            'edit' => Pages\EditCashbackRule::route('/{record}/edit'),
        ];
    }
}
