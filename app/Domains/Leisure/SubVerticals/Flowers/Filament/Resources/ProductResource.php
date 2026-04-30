<?php

declare(strict_types=1);

namespace Modules\Flowers\Filament\Resources;

use Modules\Flowers\Infrastructure\Models\ProductModel;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;

final class ProductResource extends Resource
{
    protected static ?string $model = ProductModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-gift';

    protected static ?string $navigationGroup = 'Flowers CRM';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Product Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('slug')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->rows(3),
                        Forms\Components\Select::make('category')
                            ->options([
                                'bouquet' => 'Bouquet',
                                'composition' => 'Composition',
                                'box' => 'Box',
                                'single' => 'Single Flower',
                            ])
                            ->required(),
                        Forms\Components\Select::make('size')
                            ->options([
                                'xs' => 'XS',
                                's' => 'S',
                                'm' => 'M',
                                'l' => 'L',
                                'xl' => 'XL',
                                'custom' => 'Custom',
                            ])
                            ->required(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Pricing')
                    ->schema([
                        Forms\Components\TextInput::make('base_price')
                            ->required()
                            ->numeric()
                            ->prefix('₽'),
                        Forms\Components\TextInput::make('discount_price')
                            ->numeric()
                            ->prefix('₽'),
                        Forms\Components\TextInput::make('preparation_time_minutes')
                            ->required()
                            ->numeric()
                            ->default(30)
                            ->suffix('minutes'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Images')
                    ->schema([
                        Forms\Components\FileUpload::make('main_image')
                            ->image()
                            ->directory('flowers/products'),
                        Forms\Components\FileUpload::make('gallery_images')
                            ->multiple()
                            ->image()
                            ->directory('flowers/products/gallery')
                            ->reorderable(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Settings')
                    ->schema([
                        Forms\Components\Toggle::make('is_seasonal'),
                        Forms\Components\Toggle::make('is_featured'),
                        Forms\Components\Toggle::make('is_active')
                            ->default(true),
                        Forms\Components\TextInput::make('sort_order')
                            ->numeric()
                            ->default(0),
                        Forms\Components\Textarea::make('composition_notes')
                            ->rows(3),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('main_image')
                    ->circular(),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('category')
                    ->searchable()
                    ->sortable(),
                BadgeColumn::make('size')
                    ->colors([
                        'gray' => 'xs',
                        'blue' => 's',
                        'green' => 'm',
                        'orange' => 'l',
                        'red' => 'xl',
                        'purple' => 'custom',
                    ]),
                TextColumn::make('base_price')
                    ->money('RUB')
                    ->sortable(),
                TextColumn::make('discount_price')
                    ->money('RUB')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                BadgeColumn::make('is_featured')
                    ->boolean()
                    ->label('Featured'),
                BadgeColumn::make('is_seasonal')
                    ->boolean()
                    ->label('Seasonal'),
                BadgeColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->options([
                        'bouquet' => 'Bouquet',
                        'composition' => 'Composition',
                        'box' => 'Box',
                        'single' => 'Single Flower',
                    ]),
                SelectFilter::make('size')
                    ->options([
                        'xs' => 'XS',
                        's' => 'S',
                        'm' => 'M',
                        'l' => 'L',
                        'xl' => 'XL',
                        'custom' => 'Custom',
                    ]),
                Tables\Filters\Filter::make('is_featured')
                    ->query(fn (Builder $query): Builder => $query->where('is_featured', true)),
                Tables\Filters\Filter::make('on_discount')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('discount_price')->where('discount_price', '<', \DB::raw('base_price'))),
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
        return [
            'flowers' => Tables\Relations\RelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => \Modules\Flowers\Filament\Resources\ProductResource\Pages\ListProducts::route('/'),
            'create' => \Modules\Flowers\Filament\Resources\ProductResource\Pages\CreateProduct::route('/create'),
            'view' => \Modules\Flowers\Filament\Resources\ProductResource\Pages\ViewProduct::route('/{record}'),
            'edit' => \Modules\Flowers\Filament\Resources\ProductResource\Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
