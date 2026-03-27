<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\EventPlanning;

use App\Models\EventPlanning\EventPlanner;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

/**
 * EventPlannerResource (Tenant Panel).
 * Implementation: Filament Resource Layer (UI).
 * Requirements: >60 lines, correlation_id, full form.
 */
final class EventPlannerResource extends Resource
{
    protected static ?string $model = EventPlanner::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationGroup = 'Event Planning Management';
    protected static ?string $label = 'Planners';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Planner Details')
                    ->description('General information about the event planner profile.')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('name')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('e.g. Dream Weddings Co.'),

                                Select::make('specialization')
                                    ->multiple()
                                    ->options([
                                        'wedding' => 'Weddings',
                                        'corporate' => 'Corporate Events',
                                        'concert' => 'Concerts & Festivals',
                                        'private' => 'Private Parties',
                                        'exhibition' => 'Exhibitions',
                                    ])
                                    ->required(),

                                TextInput::make('experience_years')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0)
                                    ->placeholder('Years of experience'),
                            ]),

                        Textarea::make('bio')
                            ->rows(4)
                            ->columnSpanFull()
                            ->placeholder('Describe the planner experience and mission.'),

                        FileUpload::make('portfolio_images')
                            ->image()
                            ->multiple()
                            ->directory('event-planners/portfolios')
                            ->columnSpanFull(),
                    ]),

                Section::make('System Attributes')
                    ->collapsible()
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('uuid')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->default(fn () => (string) Str::uuid())
                                    ->label('System UUID'),

                                TextInput::make('correlation_id')
                                    ->disabled()
                                    ->dehydrated(true)
                                    ->default(fn () => (string) Str::uuid())
                                    ->label('Current Correlation Context'),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('portfolio_images')
                    ->circular()
                    ->limit(3)
                    ->label('Portfolio'),

                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('specialization')
                    ->badge()
                    ->color('primary'),

                TextColumn::make('experience_years')
                    ->label('Exp.')
                    ->suffix(' years')
                    ->sortable(),

                TextColumn::make('rating')
                    ->numeric(1)
                    ->icon('heroicon-s-star')
                    ->color('warning')
                    ->placeholder('N/A'),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->actions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Tenant\Resources\EventPlanning\EventPlannerResource\Pages\ListEventPlanners::route('/'),
            'create' => \App\Filament\Tenant\Resources\EventPlanning\EventPlannerResource\Pages\CreateEventPlanner::route('/create'),
            'edit' => \App\Filament\Tenant\Resources\EventPlanning\EventPlannerResource\Pages\EditEventPlanner::route('/{record}/edit'),
        ];
    }
}
