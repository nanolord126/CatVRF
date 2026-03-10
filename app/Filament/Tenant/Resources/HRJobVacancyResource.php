<?php

namespace App\Filament\Tenant\Resources;

use App\Models\HRJobVacancy;
use App\Models\User;
use App\Services\HR\AIJobMatchingService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

class HRJobVacancyResource extends Resource
{
    protected static ?string $model = HRJobVacancy::class;
    protected static ?string $navigationIcon = 'heroicon-o-briefcase';
    protected static ?string $navigationGroup = 'Internal Recruitment';
    protected static ?string $slug = 'internal-hr-job-board';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('General Information')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->required()
                            ->columnSpanFull(),
                    ]),
                
                Forms\Components\Section::make('Requirements & Finance')
                    ->schema([
                        Forms\Components\Select::make('vertical')
                            ->options([
                                'Taxi' => 'Taxi & Transportation',
                                'Food' => 'Food & Restaurants',
                                'Retail' => 'Retail & Shops',
                                'Clinics' => 'Clinics & Healthcare',
                                'Education' => 'Education & Sports',
                            ])
                            ->required(),
                        Forms\Components\TagsInput::make('skills')
                            ->placeholder('Enter technical skills')
                            ->required(),
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('salary_min')
                                    ->numeric()
                                    ->prefix('$'),
                                Forms\Components\TextInput::make('salary_max')
                                    ->numeric()
                                    ->prefix('$'),
                                Forms\Components\Select::make('status')
                                    ->options([
                                        'open' => 'Active',
                                        'closed' => 'Closed',
                                        'on_hold' => 'On Hold',
                                    ])
                                    ->default('open')
                                    ->required(),
                            ]),
                    ]),

                Forms\Components\Section::make('Geo-Recruitment Tracking')
                    ->schema([
                        Forms\Components\TextInput::make('location_name'),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('latitude')->numeric(),
                                Forms\Components\TextInput::make('longitude')->numeric(),
                            ]),
                    ])->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'open',
                        'danger' => 'closed',
                        'warning' => 'on_hold',
                    ]),
                Tables\Columns\TextColumn::make('vertical')
                    ->badge(),
                Tables\Columns\TextColumn::make('salary_range')
                    ->label('Salary (USD)')
                    ->getStateUsing(fn (HRJobVacancy $record) => "$record->salary_min - $record->salary_max"),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('vertical'),
                SelectFilter::make('status'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Action::make('Run AI Matching')
                    ->icon('heroicon-o-sparkles')
                    ->color('info')
                    ->action(function (HRJobVacancy $record, AIJobMatchingService $matchingService) {
                        $matches = $matchingService->getRecommendedCandidates($record);
                        
                        Notification::make()
                            ->title('AI Recommendations Generated')
                            ->body("Found {$matches->count()} candidates in the ecosystem. Top similarity score: " . (round($matches->first()['score'] ?? 0, 2) * 100) . "%")
                            ->success()
                            ->send();
                    })
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // Relation for matches
            HRJobVacancyResource\RelationManagers\MatchesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => HRJobVacancyResource\Pages\ListHRJobVacancies::route('/'),
            'create' => HRJobVacancyResource\Pages\CreateHRJobVacancy::route('/create'),
            'edit' => HRJobVacancyResource\Pages\EditHRJobVacancy::route('/{record}/edit'),
        ];
    }
}
