<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use App\Domains\Food\Models\CorporateOrder;
use App\Filament\Tenant\Resources\OfficeCateringResource\Pages;
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
 * OfficeCateringResource
 * 
 * Управление ресурсом на базе КАНОН 2026.
 * Production-ready implementation.
 */
final class OfficeCateringResource extends Resource
{
    protected static ?string $model = CorporateOrder::class;

    protected static ?string $navigationIcon = 'heroicon-o-briefcase';

    protected static ?string $navigationGroup = 'Catering';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Основная информация')
                    ->description('Базовые сведения об объекте')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                                TextInput::make('company_name')
                    ->required()
                    ->maxLength(255),
                                DatePicker::make('order_date')
                    ->required(),
                                TextInput::make('employee_count')
                    ->required()
                    ->maxLength(255),
                                Select::make('menu_type')
                    ->required()
                    ->searchable(),
                                TextInput::make('total_price')
                    ->required()
                    ->maxLength(255),
                                Select::make('status')
                    ->required()
                    ->searchable(),
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
            'index' => Pages\\ListOfficeCatering::route('/'),
            'create' => Pages\\CreateOfficeCatering::route('/create'),
            'edit' => Pages\\EditOfficeCatering::route('/{record}/edit'),
            'view' => Pages\\ViewOfficeCatering::route('/{record}'),
        ];

    public static function getPages(): array
    {
        return [
            'index' => Pages\\ListOfficeCatering::route('/'),
            'create' => Pages\\CreateOfficeCatering::route('/create'),
            'edit' => Pages\\EditOfficeCatering::route('/{record}/edit'),
            'view' => Pages\\ViewOfficeCatering::route('/{record}'),
        ];
    }
}
