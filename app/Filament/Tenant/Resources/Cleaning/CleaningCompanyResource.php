<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Cleaning;

use App\Models\Cleaning\CleaningCompany;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\TagsInput;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Exceptions\ActionNotFoundException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * CleaningCompanyResource.
 * Management of the cleaning service providers.
 * Part of 2026 Canonical vertical implementation.
 */
final class CleaningCompanyResource extends Resource
{
    protected static ?string $model = CleaningCompany::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';
    protected static ?string $navigationGroup = 'Cleaning Services';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Group::make()
                    ->schema([
                        Section::make('Core Identity')
                            ->description('Basic legal and brand information for the company.')
                            ->schema([
                                TextInput::make('name')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('e.g., CleanPro Services Ltd.'),
                                    
                                TextInput::make('inn')
                                    ->label('Tax ID (INN)')
                                    ->length(10)
                                    ->numeric()
                                    ->helperText('Required for B2B contracts')
                                    ->placeholder('77XXXXXXXX'),
                                    
                                Select::make('type')
                                    ->options([
                                        'local' => 'Local Provider',
                                        'aggregator' => 'Aggregator/Marketplace',
                                        'premium' => 'Premium/Elite Service',
                                        'industrial' => 'Industrial/Commercial Only',
                                    ])
                                    ->default('local')
                                    ->required(),
                                    
                                Toggle::make('is_verified')
                                    ->label('Verified by Platform')
                                    ->onIcon('heroicon-m-check-badge')
                                    ->offIcon('heroicon-m-x-circle')
                                    ->default(false),
                            ])
                            ->columns(2),

                        Section::make('Operation Settings')
                            ->description('JSONB configuration for the company behavior.')
                            ->schema([
                                TagsInput::make('tags')
                                    ->placeholder('Add service tags: eco-friendly, fast, 24/7'),
                                    
                                Textarea::make('settings.description')
                                    ->label('Public Bio')
                                    ->rows(5)
                                    ->columnSpanFull(),
                                    
                                TextInput::make('settings.commission_percent')
                                    ->label('Platform Commission (%)')
                                    ->numeric()
                                    ->default(14)
                                    ->suffix('%')
                                    ->required(),
                            ])
                            ->columns(2),
                    ])
                    ->columnSpan(['lg' => 2]),

                Group::make()
                    ->schema([
                        Section::make('Metadata/Security')
                            ->schema([
                                TextInput::make('uuid')
                                    ->disabled()
                                    ->label('Entity UUID')
                                    ->placeholder('System generated'),
                                    
                                TextInput::make('correlation_id')
                                    ->disabled()
                                    ->label('Active Trace ID')
                                    ->placeholder('Active correlation ID'),
                                    
                                TextInput::make('rating')
                                    ->disabled()
                                    ->numeric()
                                    ->label('Average Rating')
                                    ->default(5.00)
                                    ->step(0.01),
                                    
                                TextInput::make('created_at')
                                    ->disabled()
                                    ->label('Registry Date')
                                    ->placeholder('System creation time'),
                            ]),
                    ])
                    ->columnSpan(['lg' => 1]),
            ])
            ->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (CleaningCompany $record) => $record->type),
                
                TextColumn::make('inn')
                    ->label('INN')
                    ->copyable()
                    ->searchable(),
                    
                IconColumn::make('is_verified')
                    ->boolean()
                    ->label('Verified')
                    ->sortable(),
                    
                TextColumn::make('rating')
                    ->label('Rating')
                    ->numeric(decimalPlaces: 2)
                    ->color('warning')
                    ->sortable(),
                    
                TextColumn::make('services_count')
                    ->counts('services')
                    ->label('Services Offered')
                    ->badge(),
                    
                TextColumn::make('created_at')
                    ->dateTime()
                    ->label('Joined At')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options([
                        'local' => 'Local',
                        'aggregator' => 'Aggregator',
                        'premium' => 'Premium',
                        'industrial' => 'Industrial',
                    ]),
                \Filament\Tables\Filters\TernaryFilter::make('is_verified'),
            ])
            ->actions([
                \Filament\Tables\Actions\EditAction::make(),
                \Filament\Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                \Filament\Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withCount('services')
            ->orderBy('is_verified', 'desc')
            ->orderBy('rating', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Tenant\Resources\Cleaning\Pages\ListCleaningCompanies::route('/'),
            'create' => \App\Filament\Tenant\Resources\Cleaning\Pages\CreateCleaningCompany::route('/create'),
            'edit' => \App\Filament\Tenant\Resources\Cleaning\Pages\EditCleaningCompany::route('/{record}/edit'),
        ];
    }
}
