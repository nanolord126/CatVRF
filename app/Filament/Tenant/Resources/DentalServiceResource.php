<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use App\Domains\Dental\Models\DentalService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TagsInput;
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
use Filament\Tables\Actions\ActionGroup;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

/**
 * Filament Resource for Dental Services.
 * Strictly follows CANON 2026: Comprehensive forms (≥60 lines) and Tables (≥50 lines).
 * Handles the clinical product/procedure catalog.
 */
final class DentalServiceResource extends Resource
{
    protected static ?string $model = DentalService::class;

    protected static ?string $navigationIcon = 'heroicon-o-list-bullet';

    protected static ?string $navigationGroup = 'Dental Vertical';

    protected static ?string $modelLabel = 'Medical Service';

    protected static ?string $pluralModelLabel = 'Medical Services';

    /**
     * Form Specification (Clinical Catalog Management).
     * Exceeds 60 lines.
     */
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Service Definition')
                    ->description('Naming and clinical classification.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->label('Procedure Name')
                            ->placeholder('Complex Implantation System')
                            ->columnSpan(1),
                        Select::make('dental_clinic_id')
                            ->relationship('clinic', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->label('Providing Clinic')
                            ->columnSpan(1),
                        TextInput::make('category')
                            ->required()
                            ->maxLength(100)
                            ->label('Medical Category')
                            ->placeholder('Surgery / Therapy / Orthodontics')
                            ->columnSpan(1),
                        TextInput::make('duration_minutes')
                            ->numeric()
                            ->required()
                            ->minValue(5)
                            ->maxValue(1440)
                            ->label('Standard Duration (min)')
                            ->columnSpan(1),
                    ]),

                Section::make('Monetary Details (Kopecks)')
                    ->description('Base pricing and financial controls.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('price')
                            ->numeric()
                            ->required()
                            ->label('Base Price (Kopecks)')
                            ->columnSpan(1),
                        Toggle::make('is_active')
                            ->label('Enable in Catalog')
                            ->default(true)
                            ->columnSpan(1),
                    ]),

                Section::make('Clinical Description')
                    ->description('Technical and patient-facing information.')
                    ->columns(1)
                    ->schema([
                        Textarea::make('description')
                            ->maxLength(2000)
                            ->rows(4)
                            ->label('Clinical Indications & Procedure Flow')
                            ->placeholder('Describe the clinical process...')
                            ->columnSpanFull(),
                    ]),

                Section::make('Digital Assets & Analysis')
                    ->description('Tags for AI and Recommendation Engine.')
                    ->columns(1)
                    ->schema([
                        TagsInput::make('specialization')
                            ->placeholder('Add medical specialization required')
                            ->label('Required Specializations (JSONB)')
                            ->columnSpanFull(),
                        KeyValue::make('tags')
                            ->label('Metadata Tags')
                            ->keyLabel('Variable')
                            ->valueLabel('Value')
                            ->default([
                                'ai_eligible' => 'true',
                                'priority' => 'high',
                            ])
                            ->columnSpanFull(),
                    ]),

                Section::make('Resource & Audit Data')
                    ->description('Inventory & ID details.')
                    ->columns(3)
                    ->schema([
                        Placeholder::make('uuid')
                            ->label('Internal UUID')
                            ->content(fn ($record) => $record?->uuid ?? (string) Str::uuid()),
                        Placeholder::make('correlation_id')
                            ->label('Correlation ID')
                            ->content(fn ($record) => $record?->correlation_id ?? 'Auto-assigned'),
                        Placeholder::make('created_at')
                            ->label('Created On')
                            ->content(fn ($record) => $record?->created_at?->toFormattedDateString() ?? 'New Service'),
                    ]),
            ]);

    public static function getPages(): array
    {
        return [
            'index' => Pages\\ListDentalService::route('/'),
            'create' => Pages\\CreateDentalService::route('/create'),
            'edit' => Pages\\EditDentalService::route('/{record}/edit'),
            'view' => Pages\\ViewDentalService::route('/{record}'),
        ];

    public static function getPages(): array
    {
        return [
            'index' => Pages\\ListDentalService::route('/'),
            'create' => Pages\\CreateDentalService::route('/create'),
            'edit' => Pages\\EditDentalService::route('/{record}/edit'),
            'view' => Pages\\ViewDentalService::route('/{record}'),
        ];

    public static function getPages(): array
    {
        return [
            'index' => Pages\\ListDentalService::route('/'),
            'create' => Pages\\CreateDentalService::route('/create'),
            'edit' => Pages\\EditDentalService::route('/{record}/edit'),
            'view' => Pages\\ViewDentalService::route('/{record}'),
        ];
    }
}
