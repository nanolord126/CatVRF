<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Flowers;

use App\Domains\Flowers\Models\Bouquet;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

final class BouquetResource extends Resource
{
    protected static ?string $model = Bouquet::class;
    protected static ?string $navigationIcon = 'heroicon-o-sparkles';
    protected static ?string $navigationGroup = 'Flowers';
    protected static ?string $navigationLabel = 'Bouquets';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('General Information')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\Textarea::make('description')
                        ->maxLength(65535)
                        ->columnSpanFull(),
                    Forms\Components\FileUpload::make('image_url')
                        ->image()
                        ->directory('bouquets')
                        ->columnSpanFull(),
                ])->columns(2),

            Forms\Components\Section::make('Inventory & Pricing')
                ->schema([
                    Forms\Components\TextInput::make('price_kopecks')
                        ->label('Price (in Kopecks)')
                        ->required()
                        ->numeric()
                        ->minValue(0),
                    Forms\Components\TextInput::make('current_stock')
                        ->required()
                        ->numeric()
                        ->minValue(0),
                    Forms\Components\Toggle::make('is_active')
                        ->default(true),
                ])->columns(3),

            Forms\Components\Section::make('Composition (JSON)')
                ->schema([
                    Forms\Components\Repeater::make('composition')
                        ->schema([
                            Forms\Components\Select::make('product_id')
                                ->relationship('products', 'name')
                                ->required(),
                            Forms\Components\TextInput::make('quantity')
                                ->required()
                                ->numeric()
                                ->minValue(1),
                        ])
                        ->columns(2)
                        ->columnSpanFull(),
                ]),

            Forms\Components\Section::make('System Fields')
                ->schema([
                    Forms\Components\TextInput::make('uuid')
                        ->disabled()
                        ->dehydrated(false)
                        ->placeholder('Generated on save'),
                    Forms\Components\TextInput::make('tenant_id')
                        ->disabled()
                        ->dehydrated(false)
                        ->default(fn () => tenant()->id ?? null),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image_url')
                    ->label('Photo')
                    ->circular(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('price_kopecks')
                    ->label('Price')
                    ->money('RUB', divideBy: 100)
                    ->sortable(),
                Tables\Columns\TextColumn::make('current_stock')
                    ->label('Stock')
                    ->badge()
                    ->color(fn ($state) => $state < 5 ? 'danger' : 'success')
                    ->sortable(),
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Active'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
                Tables\Filters\TernaryFilter::make('is_active'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBouquets::route('/'),
            'create' => Pages\CreateBouquet::route('/create'),
            'edit' => Pages\EditBouquet::route('/{record}/edit'),
        ];
    }
}
