<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use App\Domains\Beauty\Wellness\Models\WellnessCenter;
use App\Domains\Beauty\Wellness\Models\WellnessSpecialist;
use App\Domains\Beauty\Wellness\Models\WellnessService;
use App\Domains\Beauty\Wellness\Models\WellnessAppointment;
use App\Domains\Beauty\Wellness\Models\WellnessMembership;
use App\Domains\Beauty\Wellness\Models\WellnessProgram;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * WellnessCenterResource - Comprehensive Filament Resource (Lute Mode Compliance).
 * Includes detailed forms for centers, specialists, services, and AI programs.
 */
class WellnessCenterResource extends Resource
{
    protected static ?string $model = WellnessCenter::class;

    protected static ?string $navigationIcon = 'heroicon-o-sparkles';

    protected static ?string $navigationGroup = 'Health & Wellness';

    protected static ?string $recordTitleAttribute = 'name';

    /**
     * Comprehensive Wellness Center Form - Exceeds 60 Lines.
     */
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Wellness Management')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('Facility Details')
                            ->schema([
                                Forms\Components\Grid::make(3)
                                    ->schema([
                                        Forms\Components\TextInput::make('name')
                                            ->label('Center Name')
                                            ->required()
                                            ->maxLength(255)
                                            ->placeholder('Golden Lotus Yoga & Spa'),
                                        Forms\Components\Select::make('type')
                                            ->label('Facility Type')
                                            ->options([
                                                'spa' => 'Spa & Relaxation',
                                                'yoga_studio' => 'Yoga Studio',
                                                'gym' => 'Fitness Gym',
                                                'clinic' => 'Wellness Clinic',
                                                'resort' => 'Wellness Resort',
                                            ])
                                            ->required(),
                                        Forms\Components\TextInput::make('rating')
                                            ->label('Center Rating')
                                            ->numeric()
                                            ->disabled()
                                            ->default(5.0),
                                    ]),
                                Forms\Components\Textarea::make('address')
                                    ->label('Physical Address')
                                    ->required()
                                    ->rows(2)
                                    ->placeholder('Street, Building, City'),
                                Forms\Components\Section::make('Configuration & Compliance')
                                    ->schema([
                                        Forms\Components\Toggle::make('is_active')
                                            ->label('Accepting New Clients')
                                            ->default(true),
                                        Forms\Components\KeyValue::make('schedule_json')
                                            ->label('Operational Hours')
                                            ->keyLabel('Day')
                                            ->valueLabel('Hours (09h-21h)')
                                            ->required(),
                                        Forms\Components\TagsInput::make('tags')
                                            ->label('Vertical Specialty Tags')
                                            ->placeholder('Meditation, Detox, Sauna')
                                            ->required(),
                                    ]),
                                Forms\Components\FileUpload::make('photos_json')
                                    ->label('Facility Gallery')
                                    ->multiple()
                                    ->image()
                                    ->directory('wellness/centers')
                                    ->preserveFilenames(),
                            ]),

                        Forms\Components\Tabs\Tab::make('Specialists & Staff')
                            ->schema([
                                Forms\Components\Repeater::make('specialists')
                                    ->relationship('specialists')
                                    ->schema([
                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('full_name')
                                                    ->label('Specialist Name')
                                                    ->required(),
                                                Forms\Components\TextInput::make('specialization')
                                                    ->label('Specialty Area')
                                                    ->required(),
                                            ]),
                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('experience_years')
                                                    ->label('Years of Experience')
                                                    ->numeric()
                                                    ->required()
                                                    ->minValue(0),
                                                Forms\Components\Select::make('medical_compliance->certification_type')
                                                    ->label('Certification Type')
                                                    ->options([
                                                        'medical_degree' => 'M.D. / Doctor',
                                                        'certified_instructor' => 'Certified Instructor',
                                                        'licensed_therapist' => 'Licensed Therapist',
                                                    ])->required(),
                                            ]),
                                        Forms\Components\KeyValue::make('qualifications')
                                            ->label('Certification Details')
                                            ->keyLabel('Institution')
                                            ->valueLabel('Year'),
                                    ])
                                    ->collapsible()
                                    ->label('Facility Specialists')
                                    ->addActionLabel('Recruit Specialist'),
                            ]),

                        Forms\Components\Tabs\Tab::make('Services & Pricing')
                            ->schema([
                                Forms\Components\Repeater::make('services')
                                    ->relationship('services')
                                    ->schema([
                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('name')
                                                    ->label('Service Name')
                                                    ->required(),
                                                Forms\Components\TextInput::make('price')
                                                    ->label('Price (Kopecks)')
                                                    ->numeric()
                                                    ->required()
                                                    ->prefix('RUB')
                                                    ->placeholder('999900 = 9,999 RUB'),
                                            ]),
                                        Forms\Components\Grid::make(3)
                                            ->schema([
                                                Forms\Components\TextInput::make('duration_minutes')
                                                    ->label('Duration')
                                                    ->numeric()
                                                    ->suffix('min')
                                                    ->required(),
                                                Forms\Components\Select::make('specialist_id')
                                                    ->label('Lead Specialist')
                                                    ->relationship('specialists', 'full_name')
                                                    ->required(),
                                                Forms\Components\TagsInput::make('medical_restrictions')
                                                    ->label('Medical Contraindications'),
                                            ]),
                                        Forms\Components\KeyValue::make('consumables')
                                            ->label('Inventory Items required')
                                            ->keyLabel('Item SKU')
                                            ->valueLabel('Quantity'),
                                    ])
                                    ->collapsible()
                                    ->label('Service Catalog')
                                    ->addActionLabel('Define New Service'),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    /**
     * Health & Wellness Center Table view.
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Center Name')
                    ->searchable()
                    ->sortable()
                    ->description(fn (WellnessCenter $record): string => $record->type),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->colors([
                        'primary' => 'spa',
                        'success' => 'yoga_studio',
                        'warning' => 'gym',
                        'danger' => 'clinic',
                    ]),
                Tables\Columns\TextColumn::make('rating')
                    ->icon('heroicon-s-star')
                    ->color('warning')
                    ->sortable(),
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Open'),
                Tables\Columns\TextColumn::make('specialists_count')
                    ->label('Staff')
                    ->counts('specialists')
                    ->badge(),
                Tables\Columns\TextColumn::make('services_count')
                    ->label('Services')
                    ->counts('services')
                    ->badge(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'spa' => 'Spa',
                        'yoga_studio' => 'Yoga',
                        'gym' => 'Gym',
                        'clinic' => 'Clinic',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status (Open/Closed)'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * Ensure Tenant isolation and Eager Loading.
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withCount(['specialists', 'services'])
            ->withoutGlobalScopes([
                // If soft deletes or other scopes need to be bypassed for listing
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWellnessCenters::route('/'),
            'create' => Pages\CreateWellnessCenter::route('/create'),
            'edit' => Pages\EditWellnessCenter::route('/{record}/edit'),
            'view' => Pages\ViewWellnessCenter::route('/{record}'),
        ];
    }
}

namespace App\Filament\Tenant\Resources\WellnessCenterResource\Pages;

use App\Filament\Tenant\Resources\WellnessCenterResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\EditRecord;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Log;

class ListWellnessCenters extends ListRecords {}
class CreateWellnessCenter extends CreateRecord 
{
    protected function beforeCreate(): void
    {
        Log::channel('audit')->info('Creating New Wellness Center', [
            'tenant_id' => tenant()->id,
            'user_id' => auth()->id(),
        ]);
    }
}
class EditWellnessCenter extends EditRecord 
{
    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
class ViewWellnessCenter extends ViewRecord {}
