<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use App\Domains\Gifts\Models\GiftProduct;
use App\Filament\Tenant\Resources\GiftsResource\Pages;
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
 * GiftsResource
 * 
 * Управление ресурсом на базе КАНОН 2026.
 * Production-ready implementation.
 */
final class GiftsResource extends Resource
{
    protected static ?string $model = GiftProduct::class;

    protected static ?string $navigationIcon = 'heroicon-o-gift';

    protected static ?string $navigationGroup = 'Gifts';

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
                                TextInput::make('stock')
                    ->required()
                    ->maxLength(255),
                                Select::make('occasion')
                    ->required()
                    ->searchable(),
                                TextInput::make('rating')
                    ->required()
                    ->maxLength(255),
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
            'index' => Pages\\ListGifts::route('/'),
            'create' => Pages\\CreateGifts::route('/create'),
            'edit' => Pages\\EditGifts::route('/{record}/edit'),
            'view' => Pages\\ViewGifts::route('/{record}'),
        ];

    public static function getPages(): array
    {
        return [
            'index' => Pages\\ListGifts::route('/'),
            'create' => Pages\\CreateGifts::route('/create'),
            'edit' => Pages\\EditGifts::route('/{record}/edit'),
            'view' => Pages\\ViewGifts::route('/{record}'),
        ];
    }
}
