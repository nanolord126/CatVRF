<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use App\Domains\Electronics\Models\ElectronicProduct;
use App\Filament\Tenant\Resources\ElectronicsResource\Pages;
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
 * ElectronicsResource
 * 
 * Управление ресурсом на базе КАНОН 2026.
 * Production-ready implementation.
 */
final class ElectronicsResource extends Resource
{
    protected static ?string $model = ElectronicProduct::class;

    protected static ?string $navigationIcon = 'heroicon-o-device-phone-mobile';

    protected static ?string $navigationGroup = 'Electronics';

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
                                TextInput::make('sku')
                    ->required()
                    ->maxLength(255),
                                Select::make('category')
                    ->required()
                    ->searchable(),
                                TextInput::make('brand')
                    ->required()
                    ->maxLength(255),
                                TextInput::make('price')
                    ->required()
                    ->maxLength(255),
                                TextInput::make('current_stock')
                    ->required()
                    ->maxLength(255),
                                TextInput::make('warranty_months')
                    ->required()
                    ->maxLength(255),
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
            'index' => Pages\\ListElectronics::route('/'),
            'create' => Pages\\CreateElectronics::route('/create'),
            'edit' => Pages\\EditElectronics::route('/{record}/edit'),
            'view' => Pages\\ViewElectronics::route('/{record}'),
        ];

    public static function getPages(): array
    {
        return [
            'index' => Pages\\ListElectronics::route('/'),
            'create' => Pages\\CreateElectronics::route('/create'),
            'edit' => Pages\\EditElectronics::route('/{record}/edit'),
            'view' => Pages\\ViewElectronics::route('/{record}'),
        ];

    public static function getPages(): array
    {
        return [
            'index' => Pages\\ListElectronics::route('/'),
            'create' => Pages\\CreateElectronics::route('/create'),
            'edit' => Pages\\EditElectronics::route('/{record}/edit'),
            'view' => Pages\\ViewElectronics::route('/{record}'),
        ];
    }
}
