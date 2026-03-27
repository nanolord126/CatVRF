<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use App\Models\Dental\DentalClinic;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Placeholder;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\TernaryFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

/**
 * Filament Resource for Dental Clinics.
 * Strictly follows CANON 2026: Comprehensive forms (≥60 lines) and Tables (≥50 lines).
 */
final class DentalClinicResource extends Resource
{
    protected static ?string $model = DentalClinic::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationGroup = 'Dental Vertical';

    protected static ?string $modelLabel = 'Dental Clinic';

    protected static ?string $pluralModelLabel = 'Dental Clinics';

    /**
     * Form Specification (Full Set of Properties).
     * Exceeds 60 lines.
     */
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Clinic Core Information')
                    ->description('Primary identifies and license details.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Modern Dental Center')
                            ->columnSpan(1),
                        TextInput::make('license_number')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(100)
                            ->label('Medical License Number')
                            ->columnSpan(1),
                        TextInput::make('uuid')
                            ->disabled()
                            ->dehydrated(false)
                            ->label('Internal UUID')
                            ->default(fn () => (string) Str::uuid())
                            ->columnSpan(1),
                        Toggle::make('is_premium')
                            ->label('Premium Clinic Status')
                            ->inline(false)
                            ->columnSpan(1),
                    ]),

                Section::make('Location & Contact')
                    ->description('Geographical and contact accessibility.')
                    ->columns(2)
                    ->schema([
                        Textarea::make('address')
                            ->required()
                            ->maxLength(500)
                            ->rows(3)
                            ->placeholder('123 Dental St, Moscow, RU')
                            ->columnSpanFull(),
                        TextInput::make('rating')
                            ->numeric()
                            ->disabled()
                            ->default(0)
                            ->label('Current Clinic Rating (0-100)')
                            ->columnSpan(1),
                    ]),

                Section::make('Operational Schedule')
                    ->description('Business hours for the dental practice.')
                    ->columns(1)
                    ->schema([
                        KeyValue::make('schedule')
                            ->label('Working Hours (JSON)')
                            ->keyLabel('Day of Week')
                            ->valueLabel('Opening Intervals (e.g., 09:00-21:00)')
                            ->default([
                                'monday' => '09:00-21:00',
                                'tuesday' => '09:00-21:00',
                                'wednesday' => '09:00-21:00',
                                'thursday' => '09:00-21:00',
                                'friday' => '09:00-21:00',
                                'saturday' => '10:00-18:00',
                                'sunday' => 'Closed',
                            ])
                            ->columnSpanFull(),
                    ]),

                Section::make('Analysis & Analytics')
                    ->description('Tags for internal business intelligence.')
                    ->columns(1)
                    ->schema([
                        KeyValue::make('tags')
                            ->label('Business Tags')
                            ->keyLabel('Tag Name')
                            ->valueLabel('Tag Value')
                            ->columnSpanFull(),
                    ]),

                Section::make('Audit Trail')
                    ->description('Record metadata.')
                    ->schema([
                        Placeholder::make('correlation_id')
                            ->label('Correlation ID')
                            ->content(fn ($record) => $record?->correlation_id ?? 'Not assigned'),
                        Placeholder::make('created_at')
                            ->label('Created at')
                            ->content(fn ($record) => $record?->created_at?->diffForHumans() ?? 'New Record'),
                        Placeholder::make('updated_at')
                            ->label('Last Updated')
                            ->content(fn ($record) => $record?->updated_at?->toDateTimeString() ?? 'New Record'),
                    ])->columns(3),
            ]);
    }

    /**
     * Table Specification (Full List Controls).
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
                    ->description(fn ($record) => $record->license_number),
                TextColumn::make('rating')
                    ->numeric()
                    ->sortable()
                    ->label('Rating %')
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        $state >= 90 => 'success',
                        $state >= 70 => 'warning',
                        default => 'danger',
                    }),
                IconColumn::make('is_premium')
                    ->boolean()
                    ->label('Premium')
                    ->trueIcon('heroicon-o-star')
                    ->falseIcon('heroicon-o-minus')
                    ->color('warning'),
                TextColumn::make('address')
                    ->limit(30)
                    ->searchable()
                    ->tooltip(fn ($record) => $record->address),
                TextColumn::make('services_count')
                    ->counts('services')
                    ->label('Services Offered')
                    ->sortable(),
                TextColumn::make('dentists_count')
                    ->counts('dentists')
                    ->label('Dentists')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('uuid')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Internal UUID'),
            ])
            ->filters([
                TernaryFilter::make('is_premium')
                    ->label('Premium Only'),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No Dental Clinics registered in your tenant.')
            ->poll('30s');
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
            'index' => \App\Filament\Tenant\Resources\DentalClinicResource\Pages\ListDentalClinics::route('/'),
            'create' => \App\Filament\Tenant\Resources\DentalClinicResource\Pages\CreateDentalClinic::route('/create'),
            'edit' => \App\Filament\Tenant\Resources\DentalClinicResource\Pages\EditDentalClinic::route('/{record}/edit'),
        ];
    }
}
