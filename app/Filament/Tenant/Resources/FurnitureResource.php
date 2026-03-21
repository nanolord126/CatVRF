<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use App\Domains\Furniture\Models\Furniture;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class FurnitureResource extends Resource
{
    protected static ?string $model = Furniture::class;
    protected static ?string $navigationIcon = 'heroicon-o-sofa';
    protected static ?string $navigationGroup = 'Retail';

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('name')->required()->maxLength(255),
            TextInput::make('sku')->required()->unique(ignoreRecord: true),
            Select::make('category')->options([
                'sofa' => 'Диван', 'chair' => 'Кресло', 'table' => 'Стол',
                'bed' => 'Кровать', 'cabinet' => 'Шкаф', 'shelf' => 'Полка',
            ])->required(),
            Select::make('material')->options([
                'wood' => 'Дерево', 'metal' => 'Металл', 'leather' => 'Кожа',
                'fabric' => 'Ткань', 'ceramic' => 'Керамика',
            ])->required(),
            TextInput::make('price')->numeric()->required(),
            TextInput::make('current_stock')->numeric(),
            TextInput::make('rating')->numeric(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->searchable(),
            TextColumn::make('sku')->searchable(),
            TextColumn::make('category'),
            TextColumn::make('material'),
            TextColumn::make('price')->formatStateUsing(fn($s) => $s . ' ₽'),
            TextColumn::make('current_stock'),
            TextColumn::make('rating'),
        ])->actions([
            \Filament\Tables\Actions\EditAction::make(),
        ])->bulkActions([
            \Filament\Tables\Actions\BulkDeleteAction::make(),
        ]);
    }
}
