<?php

declare(strict_types=1);

namespace Modules\Hotels\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Hotels\Infrastructure\Models\HousekeepingTaskModel;
use Modules\Hotels\Filament\Resources\HousekeepingResource\Pages;

final class HousekeepingResource extends Resource
{
    protected static ?string $model = HousekeepingTaskModel::class;
    protected static ?string $navigationIcon = 'heroicon-o-sparkles';
    protected static ?string $navigationGroup = 'Hotels CRM';
    protected static ?int $navigationSort = 6;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Task Information')
                    ->schema([
                        Forms\Components\Select::make('venue_id')
                            ->label('Venue')
                            ->relationship('venue', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('room_id')
                            ->label('Room')
                            ->relationship('room', 'room_number')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('task_type')
                            ->label('Task Type')
                            ->options([
                                'clean' => 'Clean',
                                'deep_clean' => 'Deep Clean',
                                'turn_down' => 'Turn Down',
                                'inspection' => 'Inspection',
                                'maintenance' => 'Maintenance',
                            ])
                            ->required(),
                        Forms\Components\Select::make('priority')
                            ->label('Priority')
                            ->options([
                                'low' => 'Low',
                                'normal' => 'Normal',
                                'high' => 'High',
                                'urgent' => 'Urgent',
                            ])
                            ->required()
                            ->default('normal'),
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'pending' => 'Pending',
                                'in_progress' => 'In Progress',
                                'completed' => 'Completed',
                                'skipped' => 'Skipped',
                            ])
                            ->required()
                            ->default('pending'),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Scheduling')
                    ->schema([
                        Forms\Components\DateTimePicker::make('scheduled_for')
                            ->label('Scheduled For')
                            ->required(),
                        Forms\Components\TextInput::make('estimated_minutes')
                            ->label('Estimated Minutes')
                            ->numeric()
                            ->default(30),
                        Forms\Components\Select::make('assigned_to')
                            ->label('Assigned To')
                            ->relationship('assignedToUser', 'name')
                            ->searchable()
                            ->preload(),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Checklist')
                    ->schema([
                        Forms\Components\KeyValue::make('checklist')
                            ->label('Checklist Items')
                            ->keyLabel('Item')
                            ->valueLabel('Completed')
                            ->valueFormatStateUsing(fn ($state) => $state ? 'Yes' : 'No'),
                    ])
                    ->columns(1),

                Forms\Components\Section::make('Completion')
                    ->schema([
                        Forms\Components\DateTimePicker::make('started_at')
                            ->label('Started At')
                            ->disabled(),
                        Forms\Components\DateTimePicker::make('completed_at')
                            ->label('Completed At')
                            ->disabled(),
                        Forms\Components\TextInput::make('actual_minutes')
                            ->label('Actual Minutes')
                            ->numeric()
                            ->disabled(),
                        Forms\Components\Select::make('completed_by')
                            ->label('Completed By')
                            ->relationship('completedByUser', 'name')
                            ->disabled(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('venue.name')
                    ->label('Venue')
                    ->searchable(),
                Tables\Columns\TextColumn::make('room.room_number')
                    ->label('Room')
                    ->searchable(),
                Tables\Columns\TextColumn::make('task_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'clean' => 'info',
                        'deep_clean' => 'primary',
                        'turn_down' => 'success',
                        'inspection' => 'warning',
                        'maintenance' => 'danger',
                    }),
                Tables\Columns\TextColumn::make('priority')
                    ->label('Priority')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'low' => 'gray',
                        'normal' => 'info',
                        'high' => 'warning',
                        'urgent' => 'danger',
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'in_progress' => 'info',
                        'completed' => 'success',
                        'skipped' => 'gray',
                    }),
                Tables\Columns\TextColumn::make('scheduled_for')
                    ->label('Scheduled')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('assignedToUser.name')
                    ->label('Assigned To')
                    ->searchable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('task_type')
                    ->label('Task Type')
                    ->options([
                        'clean' => 'Clean',
                        'deep_clean' => 'Deep Clean',
                        'turn_down' => 'Turn Down',
                        'inspection' => 'Inspection',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Pending',
                        'in_progress' => 'In Progress',
                        'completed' => 'Completed',
                    ]),
                Tables\Filters\SelectFilter::make('priority')
                    ->label('Priority')
                    ->options([
                        'low' => 'Low',
                        'normal' => 'Normal',
                        'high' => 'High',
                        'urgent' => 'Urgent',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('start')
                    ->label('Start')
                    ->icon('heroicon-o-play')
                    ->color('success')
                    ->visible(fn ($record) => $record->status === 'pending')
                    ->action(function ($record) {
                        app(\Modules\Hotels\Application\Services\HousekeepingService::class)->startTask($record->id, auth()->id());
                    }),
                Tables\Actions\Action::make('complete')
                    ->label('Complete')
                    ->icon('heroicon-o-check')
                    ->color('primary')
                    ->visible(fn ($record) => $record->status === 'in_progress')
                    ->form([
                        Forms\Components\KeyValue::make('checklist')
                            ->label('Completed Checklist')
                            ->keyLabel('Item')
                            ->valueLabel('Completed')
                            ->required(),
                        Forms\Components\Textarea::make('notes')
                            ->label('Notes'),
                    ])
                    ->action(function ($record, array $data) {
                        app(\Modules\Hotels\Application\Services\HousekeepingService::class)->completeTask(
                            $record->id,
                            auth()->id(),
                            $data['checklist'] ?? null,
                            $data['notes'] ?? null,
                        );
                    }),
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListHousekeepingTasks::route('/'),
            'create' => Pages\CreateHousekeepingTask::route('/create'),
            'view' => Pages\ViewHousekeepingTask::route('/{record}'),
            'edit' => Pages\EditHousekeepingTask::route('/{record}/edit'),
        ];
    }
}
