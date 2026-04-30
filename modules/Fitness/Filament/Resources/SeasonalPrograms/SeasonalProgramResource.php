<?php

declare(strict_types=1);

namespace Modules\Fitness\Filament\Resources\SeasonalPrograms;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Fitness\Domain\SeasonalPrograms\Enums\SeasonalProgramStatus;
use Modules\Fitness\Domain\SeasonalPrograms\Enums\SeasonalProgramType;
use Modules\Fitness\Infrastructure\Models\SeasonalPrograms\SeasonalProgramModel;

final class SeasonalProgramResource extends Resource
{
    protected static ?string $model = SeasonalProgramModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationGroup = 'Fitness - Seasonal Programs';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->label('Program Name'),
                        
                        Forms\Components\Textarea::make('description')
                            ->maxLength(65535)
                            ->label('Description')
                            ->rows(3),
                        
                        Forms\Components\Select::make('type')
                            ->options([
                                'group' => 'Group',
                                'individual' => 'Individual',
                                'hybrid' => 'Hybrid',
                            ])
                            ->default('group')
                            ->required()
                            ->label('Program Type'),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Schedule & Duration')
                    ->schema([
                        Forms\Components\DatePicker::make('period_start')
                            ->required()
                            ->label('Start Date'),
                        
                        Forms\Components\DatePicker::make('period_end')
                            ->required()
                            ->label('End Date')
                            ->after('period_start'),
                        
                        Forms\Components\TextInput::make('duration_weeks')
                            ->numeric()
                            ->default(8)
                            ->minValue(1)
                            ->maxValue(52)
                            ->required()
                            ->label('Duration (Weeks)'),
                        
                        Forms\Components\TextInput::make('sessions_per_week')
                            ->numeric()
                            ->default(3)
                            ->minValue(1)
                            ->maxValue(14)
                            ->required()
                            ->label('Sessions per Week'),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Pricing & Capacity')
                    ->schema([
                        Forms\Components\TextInput::make('price')
                            ->numeric()
                            ->prefix('₽')
                            ->default(0)
                            ->minValue(0)
                            ->required()
                            ->label('Price'),
                        
                        Forms\Components\TextInput::make('max_participants')
                            ->numeric()
                            ->minValue(1)
                            ->label('Max Participants')
                            ->helperText('Leave empty for unlimited'),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Goals & Requirements')
                    ->schema([
                        Forms\Components\KeyValue::make('goals')
                            ->label('Program Goals')
                            ->keyLabel('Goal')
                            ->valueLabel('Description')
                            ->addable()
                            ->editable()
                            ->deletable(),
                        
                        Forms\Components\Textarea::make('requirements')
                            ->maxLength(65535)
                            ->label('Requirements')
                            ->rows(3)
                            ->helperText('Equipment, fitness level, etc.'),
                    ]),
                
                Forms\Components\Section::make('Status')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->options([
                                'draft' => 'Draft',
                                'active' => 'Active',
                                'full' => 'Full',
                                'completed' => 'Completed',
                                'archived' => 'Archived',
                                'cancelled' => 'Cancelled',
                            ])
                            ->default('draft')
                            ->required()
                            ->label('Status'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->label('Program Name'),
                
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match($state) {
                        'group' => 'info',
                        'individual' => 'success',
                        'hybrid' => 'warning',
                    })
                    ->label('Type'),
                
                Tables\Columns\TextColumn::make('period_start')
                    ->date()
                    ->label('Start Date'),
                
                Tables\Columns\TextColumn::make('period_end')
                    ->date()
                    ->label('End Date'),
                
                Tables\Columns\TextColumn::make('duration_weeks')
                    ->numeric()
                    ->suffix(' weeks')
                    ->label('Duration'),
                
                Tables\Columns\TextColumn::make('price')
                    ->money('RUB')
                    ->label('Price'),
                
                Tables\Columns\TextColumn::make('current_participants')
                    ->numeric()
                    ->label('Participants')
                    ->formatStateUsing(fn ($state, $record) => $state . '/' . ($record->max_participants ?? '∞')),
                
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match($state) {
                        'draft' => 'gray',
                        'active' => 'success',
                        'full' => 'warning',
                        'completed' => 'info',
                        'archived' => 'gray',
                        'cancelled' => 'danger',
                    })
                    ->label('Status'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'active' => 'Active',
                        'full' => 'Full',
                        'completed' => 'Completed',
                        'archived' => 'Archived',
                        'cancelled' => 'Cancelled',
                    ]),
                
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'group' => 'Group',
                        'individual' => 'Individual',
                        'hybrid' => 'Hybrid',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('publish')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->status === 'draft')
                    ->action(function ($record) {
                        $service = app(\Modules\Fitness\Application\Services\SeasonalProgramService::class);
                        $service->publishProgram($record->id);
                    }),
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => \Modules\Fitness\Filament\Resources\SeasonalPrograms\Pages\ListSeasonalPrograms::route('/'),
            'create' => \Modules\Fitness\Filament\Resources\SeasonalPrograms\Pages\CreateSeasonalProgram::route('/create'),
            'edit' => \Modules\Fitness\Filament\Resources\SeasonalPrograms\Pages\EditSeasonalProgram::route('/{record}/edit'),
        ];
    }
}
