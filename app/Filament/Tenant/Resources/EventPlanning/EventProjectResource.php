<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\EventPlanning;

use App\Models\EventPlanning\EventProject;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Illuminate\Support\Str;

/**
 * EventProjectResource.
 * Implementation: Filament Resource Layer (UI).
 * Requirements: >60 lines, correlation_id, full form.
 */
final class EventProjectResource extends Resource
{
    protected static ?string $model = EventProject::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationGroup = 'Event Planning Management';
    protected static ?string $label = 'Projects';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Project Context')
                    ->description('Details regarding the specific event event project.')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('title')
                                    ->required()
                                    ->maxLength(150),

                                Select::make('event_planner_id')
                                    ->relationship('planner', 'name')
                                    ->required()
                                    ->searchable(),

                                Select::make('status')
                                    ->required()
                                    ->options([
                                        'draft' => 'Draft',
                                        'active' => 'Active',
                                        'completed' => 'Completed',
                                        'cancelled' => 'Cancelled',
                                    ])
                                    ->default('draft')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'draft' => 'gray',
                                        'active' => 'primary',
                                        'completed' => 'success',
                                        'cancelled' => 'danger',
                                    }),
                            ]),

                        Grid::make(2)
                            ->schema([
                                DatePicker::make('planned_date')
                                    ->required()
                                    ->placeholder('Date of the event'),

                                TextInput::make('guest_count')
                                    ->required()
                                    ->numeric()
                                    ->placeholder('Expected count (e.g. 150)'),
                            ]),

                        Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),

                Section::make('Financial Parameters')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('budget_planned')
                                    ->required()
                                    ->numeric()
                                    ->prefix('₽')
                                    ->suffix(' (in cents)')
                                    ->placeholder('Enter total budget allocation, e.g. 50000000'),

                                TextInput::make('budget_spent')
                                    ->numeric()
                                    ->prefix('₽')
                                    ->suffix(' (in cents)')
                                    ->default(0),
                            ]),
                    ]),

                Section::make('Traceability')
                    ->collapsible()
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('uuid')
                                    ->disabled()
                                    ->default(fn () => (string) Str::uuid())
                                    ->label('System UUID'),

                                TextInput::make('correlation_id')
                                    ->disabled()
                                    ->default(fn () => (string) Str::uuid())
                                    ->label('Correlation Trace ID'),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('planner.name')
                    ->label('Planner'),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'active' => 'primary',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                    }),

                TextColumn::make('planned_date')
                    ->date()
                    ->sortable(),

                TextColumn::make('guest_count')
                    ->numeric()
                    ->label('Guests'),

                TextColumn::make('budget_planned')
                    ->money('RUB')
                    ->label('Budget'),

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
            'index' => \App\Filament\Tenant\Resources\EventPlanning\EventProjectResource\Pages\ListEventProjects::route('/'),
            'create' => \App\Filament\Tenant\Resources\EventPlanning\EventProjectResource\Pages\CreateEventProject::route('/create'),
            'edit' => \App\Filament\Tenant\Resources\EventPlanning\EventProjectResource\Pages\EditEventProject::route('/{record}/edit'),
        ];
    }
}
