<?php

declare(strict_types=1);


namespace App\Domains\Fashion\Filament\Resources;

use App\Domains\Fashion\Models\FashionStore;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Table;

final /**
 * FashionStoreResource
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class FashionStoreResource extends Resource
{
    protected static ?string $model = FashionStore::class;

    protected static ?string $navigationGroup = 'Fashion';

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('name')->required()->maxLength(255),
            RichEditor::make('description')->columnSpanFull(),
            TextInput::make('logo_url')->url(),
            TextInput::make('cover_image_url')->url(),
            Toggle::make('is_verified')->default(false),
            Toggle::make('is_active')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->searchable(),
            TextColumn::make('owner.name'),
            TextColumn::make('product_count')->numeric(),
            TextColumn::make('rating')->numeric()->sortable(),
            IconColumn::make('is_verified')->boolean(),
            IconColumn::make('is_active')->boolean(),
        ])->filters([])->actions([])->bulkActions([]);
    }
}
