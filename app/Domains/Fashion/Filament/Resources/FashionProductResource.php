<?php declare(strict_types=1);

namespace App\Domains\Fashion\Filament\Resources;

use App\Domains\Fashion\Models\FashionProduct;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Table;

final class FashionProductResource extends Resource
{
    protected static ?string $model = FashionProduct::class;

    protected static ?string $navigationGroup = 'Fashion';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('fashion_store_id')->relationship('store', 'name')->required(),
            Select::make('category_id')->relationship('category', 'name')->required(),
            TextInput::make('name')->required(),
            TextInput::make('sku')->required()->unique(),
            TextInput::make('price')->required()->numeric()->step(0.01),
            TextInput::make('cost_price')->numeric()->step(0.01),
            TextInput::make('current_stock')->required()->numeric(),
            RichEditor::make('description')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->searchable(),
            TextColumn::make('sku')->searchable(),
            TextColumn::make('store.name'),
            TextColumn::make('price')->numeric()->sortable(),
            TextColumn::make('current_stock')->numeric(),
            BadgeColumn::make('status'),
            TextColumn::make('rating')->numeric()->sortable(),
        ])->filters([])->actions([])->bulkActions([]);
    }
}
