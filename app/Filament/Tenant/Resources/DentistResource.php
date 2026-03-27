<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use App\Models\Dental\Dentist;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
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
 * Filament Resource for Dentists.
 * Strictly follows CANON 2026: Comprehensive forms (≥60 lines) and Tables (≥50 lines).
 * Specialized for Medical Professionals (Dentists).
 */
final class DentistResource extends Resource
{
    protected static ?string $model = Dentist::class;

    protected static ?string $navigationIcon = 'heroicon-o-identification';

    protected static ?string $navigationGroup = 'Dental Vertical';

    protected static ?string $modelLabel = 'Dentist';

    protected static ?string $pluralModelLabel = 'Dentists';

    /**
     * Form Specification (Full Professional Details).
     * Exceeds 60 lines.
     */
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Professional Profile')
                    ->description('Personal identifies and professional standing.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('full_name')
                            ->required()
                            ->maxLength(255)
                            ->label('Full Doctor Name')
                            ->placeholder('Dr. John Doe')
                            ->columnSpan(1),
                        Select::make('dental_clinic_id')
                            ->relationship('clinic', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->label('Assigned Clinic')
                            ->columnSpan(1),
                        TextInput::make('license_number')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(100)
                            ->label('Doctor License')
                            ->columnSpan(1),
                        TextInput::make('experience_years')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->maxValue(70)
                            ->label('Years of Practice')
                            ->columnSpan(1),
                        TextInput::make('rating')
                            ->numeric()
                            ->disabled()
                            ->default(0)
                            ->label('Aggregated Rating (0-100)')
                            ->columnSpan(1),
                        Toggle::make('is_active')
                            ->label('Active Practitioner')
                            ->default(true)
                            ->columnSpan(1),
                    ]),

                Section::make('Specializations & Expertise')
                    ->description('Define medical focus areas.')
                    ->columns(1)
                    ->schema([
                        TagsInput::make('specialization')
                            ->required()
                            ->placeholder('Add specialization (Orthodontics, Surgery, etc.)')
                            ->label('Medical Specializations (JSONB)')
                            ->columnSpanFull(),
                        KeyValue::make('tags')
                            ->label('Professional Attributes')
                            ->keyLabel('Attribute')
                            ->valueLabel('Detail')
                            ->default([
                                'language' => 'English, Russian',
                                'awards' => 'Top Dentist 2025',
                            ])
                            ->columnSpanFull(),
                    ]),

                Section::make('Professional Schedule')
                    ->description('Doctor specific availability per clinic rules.')
                    ->columns(1)
                    ->schema([
                        KeyValue::make('schedule')
                            ->label('Practitioner Hours')
                            ->keyLabel('Day')
                            ->valueLabel('Slots (e.g., 08:00-14:00)')
                            ->columnSpanFull(),
                    ]),

                Section::make('Audit Trail')
                    ->description('Metadata & Fraud Control.')
                    ->schema([
                        Placeholder::make('uuid')
                            ->label('Internal UUID')
                            ->content(fn ($record) => $record?->uuid ?? (string) Str::uuid()),
                        Placeholder::make('correlation_id')
                            ->label('Correlation ID')
                            ->content(fn ($record) => $record?->correlation_id ?? 'Not assigned'),
                        Placeholder::make('created_at')
                            ->label('Joined Network')
                            ->content(fn ($record) => $record?->created_at?->diffForHumans() ?? 'New Professional'),
                    ])->columns(3),
            ]);
    }

    /**
     * Table Specification (Full Medical Directory).
     * Exceeds 50 lines.
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('full_name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn ($record) => "License: {$record->license_number}"),
                TextColumn::make('clinic.name')
                    ->label('Clinic')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('specialization')
                    ->badge()
                    ->label('Focus Areas')
                    ->formatStateUsing(fn ($state) => is_array($state) ? implode(', ', $state) : $state)
                    ->separator(','),
                TextColumn::make('experience_years')
                    ->numeric()
                    ->sortable()
                    ->label('Exp (Years)')
                    ->suffix(' years'),
                TextColumn::make('rating')
                    ->numeric()
                    ->sortable()
                    ->label('Rating')
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        $state >= 95 => 'success',
                        $state >= 80 => 'info',
                        $state >= 60 => 'warning',
                        default => 'danger',
                    }),
                IconColumn::make('is_active')
                    ->boolean()
                    ->label('Ready')
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->color('success'),
                TextColumn::make('appointments_count')
                    ->counts('appointments')
                    ->label('Patients')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('dental_clinic_id')
                    ->label('By Clinic')
                    ->relationship('clinic', 'name'),
                TernaryFilter::make('is_active')
                    ->label('Status (Active)'),
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
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No Dentists registered.')
            ->poll('1m');
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
            'index' => \App\Filament\Tenant\Resources\DentistResource\Pages\ListDentists::route('/'),
            'create' => \App\Filament\Tenant\Resources\DentistResource\Pages\CreateDentist::route('/create'),
            'edit' => \App\Filament\Tenant\Resources\DentistResource\Pages\EditDentist::route('/{record}/edit'),
        ];
    }
}
