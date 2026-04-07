<?php declare(strict_types=1);

namespace App\Domains\Fashion\FashionRetail\Filament\Resources;

use Filament\Resources\Resource;

final class FashionRetailProductResource extends Resource
{

    protected static ?string $model = FashionRetailProduct::class;

        protected static ?string $navigationGroup = 'Fashion Retail';

        protected static ?string $navigationLabel = 'Products';

        public static function form(Form $form): Form
        {
            return $form->schema([
                Section::make('Basic Info')->schema([
                    Select::make('shop_id')->relationship('shop', 'name')->required(),
                    Select::make('category_id')->relationship('category', 'name')->required(),
                    TextInput::make('name')->required()->maxLength(255),
                    TextInput::make('sku')->required()->unique(),
                    TextInput::make('barcode')->maxLength(255),
                ])->columns(2),

                Section::make('Pricing & Stock')->schema([
                    TextInput::make('price')->required()->numeric()->step(0.01),
                    TextInput::make('cost_price')->numeric()->step(0.01),
                    TextInput::make('discount_percent')->numeric()->step(0.01)->default(0),
                    TextInput::make('current_stock')->required()->numeric()->step(1),
                    TextInput::make('min_stock_threshold')->numeric()->step(1)->default(10),
                ])->columns(2),

                Section::make('Description & Details')->schema([
                    RichEditor::make('description')->columnSpanFull(),
                    TextInput::make('colors')->columnSpanFull(),
                    TextInput::make('sizes')->columnSpanFull(),
                ]),

                Section::make('Status')->schema([
                    Select::make('status')->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                        'discontinued' => 'Discontinued',
                    ]),
                ]),
            ]);
        }

        public static function table(Table $table): Table
        {
            return $table->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('sku')->searchable(),
                TextColumn::make('shop.name')->searchable(),
                TextColumn::make('category.name'),
                TextColumn::make('price')->numeric()->sortable(),
                TextColumn::make('current_stock')->badge()->numeric()->sortable(),
                TextColumn::make('status')->badge(),
                TextColumn::make('rating')->numeric(),
            ])->filters([])->actions([])->bulkActions([]);
        }
}
