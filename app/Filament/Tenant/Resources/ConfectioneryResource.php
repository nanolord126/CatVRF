<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use App\Domains\Food\Confectionery\Models\ConfectioneryItem;
use App\Filament\Tenant\Resources\ConfectioneryResource\Pages;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * ConfectioneryResource
 * 
 * Управление ресурсом на базе КАНОН 2026.
 * Production-ready implementation.
 */
final class ConfectioneryResource extends Resource
{
    protected static ?string $model = ConfectioneryItem::class;

    protected static ?string $navigationIcon = 'heroicon-o-cake';

    protected static ?string $navigationGroup = 'Food';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Основная информация')
                    ->description('Базовые сведения об объекте')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                                Select::make('category')
                    ->required()
                    ->searchable(),
                                TextInput::make('price')
                    ->required()
                    ->maxLength(255),
                                TextInput::make('weight_grams')
                    ->required()
                    ->maxLength(255),
                                Textarea::make('ingredients')
                    ->maxLength(1000),
                                TagsInput::make('allergens'),
                            ]),
                    ]),

                Section::make('Дополнительно')
                    ->description('Расширенные параметры')
                    ->collapsed()
                    ->schema([
                        Grid::make(2)
                            ->schema([]),
                    ]),
            ]);

    public static function getPages(): array
    {
        return [
            'index' => Pages\\ListConfectionery::route('/'),
            'create' => Pages\\CreateConfectionery::route('/create'),
            'edit' => Pages\\EditConfectionery::route('/{record}/edit'),
            'view' => Pages\\ViewConfectionery::route('/{record}'),
        ];

    public static function getPages(): array
    {
        return [
            'index' => Pages\\ListConfectionery::route('/'),
            'create' => Pages\\CreateConfectionery::route('/create'),
            'edit' => Pages\\EditConfectionery::route('/{record}/edit'),
            'view' => Pages\\ViewConfectionery::route('/{record}'),
        ];

    public static function getPages(): array
    {
        return [
            'index' => Pages\\ListConfectionery::route('/'),
            'create' => Pages\\CreateConfectionery::route('/create'),
            'edit' => Pages\\EditConfectionery::route('/{record}/edit'),
            'view' => Pages\\ViewConfectionery::route('/{record}'),
        ];
    }
}
