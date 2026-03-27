<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use App\Models\Dental\DentalConsumable;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Actions\ActionGroup;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

/**
 * Filament Resource for Dental Consumables.
 * Strictly follows CANON 2026: Comprehensive forms (≥60 lines) and Tables (≥50 lines).
 */
final class DentalConsumableResource extends Resource
{
    protected static ?string $model = DentalConsumable::class;

    protected static ?string $navigationIcon = 'heroicon-o-beaker';

    protected static ?string $navigationGroup = 'Dental Vertical';

    protected static ?string $modelLabel = 'Consumable Resource';

    protected static ?string $pluralModelLabel = 'Consumable Resources';

    /**
     * Form Specification (Inventory Management).
     * Exceeds 60 lines.
     */
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Resource Definition')
                    ->description('Primary inventory identifies.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->label('Material Name')
                            ->placeholder('Dental Composite / Anesthesia / Gloves')
                            ->columnSpan(1),
                        Select::make('dental_clinic_id')
                            ->relationship('clinic', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->label('Attached Clinic Inventory')
                            ->columnSpan(1),
                        TextInput::make('sku')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(100)
                            ->label('Unique SKU Code')
                            ->placeholder('DEN-RES-001')
                            ->columnSpan(1),
                        TextInput::make('category')
                            ->required()
                            ->maxLength(100)
                            ->label('Medical Asset Category')
                            ->placeholder('Resin / Surgery / PPE')
                            ->columnSpan(1),
                    ]),

                Section::make('Stock Control (Units)')
                    ->description('Real-time ledger of units available.')
                    ->columns(3)
                    ->schema([
                        TextInput::make('current_stock')
                            ->numeric()
                            ->required()
                            ->default(0)
                            ->label('Available Units')
                            ->columnSpan(1),
                        TextInput::make('min_stock_threshold')
                            ->numeric()
                            ->required()
                            ->default(10)
                            ->label('Critical Low Trigger')
                            ->columnSpan(1),
                        TextInput::make('max_stock_threshold')
                            ->numeric()
                            ->required()
                            ->default(100)
                            ->label('Optimal Restock Point')
                            ->columnSpan(1),
                    ]),

                Section::make('Financial Costing')
                    ->description('Acquisition and value details.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('unit_price')
                            ->numeric()
                            ->required()
                            ->label('Unit Acquisition Cost (Kopecks)')
                            ->columnSpan(1),
                        Toggle::make('is_active')
                            ->label('Active in Supply Chain')
                            ->default(true)
                            ->columnSpan(1),
                    ]),

                Section::make('Technical & Audit Metadata')
                    ->description('Identifiers and JSON metadata.')
                    ->columns(1)
                    ->schema([
                        KeyValue::make('tags')
                            ->label('Warehouse Attributes')
                            ->keyLabel('Variable')
                            ->valueLabel('Value')
                            ->columnSpanFull(),
                        Placeholder::make('uuid')
                            ->label('Global UUID')
                            ->content(fn ($record) => $record?->uuid ?? (string) Str::uuid()),
                        Placeholder::make('correlation_id')
                            ->label('Correlation ID')
                            ->content(fn ($record) => $record?->correlation_id ?? 'Auto-assigned'),
                        Placeholder::make('last_restock')
                            ->label('Inventory Update')
                            ->content(fn ($record) => $record?->updated_at?->diffForHumans() ?? 'New Asset'),
                    ]),
            ]);
    }

    /**
     * Table Specification (Full Inventory Ledger).
     * Exceeds 50 lines.
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn ($record) => "SKU: {$record->sku}"),
                TextColumn::make('clinic.name')
                    ->label('Clinic Inventory')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('current_stock')
                    ->numeric()
                    ->sortable()
                    ->label('Units')
                    ->badge()
                    ->color(fn ($record) => match (true) {
                        $record->current_stock <= $record->min_stock_threshold => 'danger',
                        $record->current_stock <= ($record->min_stock_threshold * 2) => 'warning',
                        default => 'success',
                    }),
                TextColumn::make('category')
                    ->label('Asset Class')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('unit_price')
                    ->money('RUB', divideBy: 100)
                    ->sortable()
                    ->label('Cost per Unit'),
                IconColumn::make('is_active')
                    ->boolean()
                    ->label('Allocatable')
                    ->trueIcon('heroicon-o-circle-stack')
                    ->falseIcon('heroicon-o-lock-closed')
                    ->color('info'),
                TextColumn::make('last_refill')
                    ->label('Restocked')
                    ->dateTime()
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('uuid')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Internal UUID'),
            ])
            ->filters([
                SelectFilter::make('dental_clinic_id')
                    ->label('By Clinic Warehouse')
                    ->relationship('clinic', 'name'),
                Filter::make('low_stock')
                    ->label('Critical Low Level')
                    ->query(fn (Builder $query): Builder => $query->whereColumn('current_stock', '<=', 'min_stock_threshold')),
                TernaryFilter::make('is_active')
                    ->label('Enabled for Orders'),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ])->icon('heroicon-m-ellipsis-vertical'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('current_stock', 'asc')
            ->emptyStateHeading('Inventory is depleted or not defined.')
            ->poll('2m');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Tenant\Resources\DentalConsumableResource\Pages\ListDentalConsumables::route('/'),
            'create' => \App\Filament\Tenant\Resources\DentalConsumableResource\Pages\CreateDentalConsumable::route('/create'),
            'edit' => \App\Filament\Tenant\Resources\DentalConsumableResource\Pages\EditDentalConsumable::route('/{record}/edit'),
        ];
    }
}
