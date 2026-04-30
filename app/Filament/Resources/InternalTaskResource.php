<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\InternalTaskResource\Pages;
use App\Models\InternalTask;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class InternalTaskResource extends Resource
{
    protected static ?string $model = InternalTask::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Internal Management';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Task Information')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255),
                        
                        Forms\Components\Textarea::make('description')
                            ->rows(3),
                        
                        Forms\Components\Select::make('type')
                            ->options([
                                'notification' => 'Notification',
                                'campaign' => 'Campaign',
                                'report' => 'Report',
                                'review' => 'Review',
                                'other' => 'Other',
                            ])
                            ->required()
                            ->default('notification'),
                        
                        Forms\Components\Select::make('priority')
                            ->options([
                                'low' => 'Low',
                                'medium' => 'Medium',
                                'high' => 'High',
                                'urgent' => 'Urgent',
                            ])
                            ->required()
                            ->default('medium'),
                        
                        Forms\Components\Select::make('status')
                            ->options([
                                'draft' => 'Draft',
                                'pending' => 'Pending',
                                'in_progress' => 'In Progress',
                                'review' => 'Review',
                                'completed' => 'Completed',
                                'cancelled' => 'Cancelled',
                            ])
                            ->required()
                            ->default('pending'),
                        
                        Forms\Components\DateTimePicker::make('due_date'),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Assignments')
                    ->schema([
                        Forms\Components\Select::make('assignee_id')
                            ->label('Assignee')
                            ->relationship('assignee', 'name')
                            ->searchable()
                            ->preload(),
                        
                        Forms\Components\Select::make('controller_id')
                            ->label('Controller')
                            ->relationship('controller', 'name')
                            ->searchable()
                            ->preload()
                            ->helperText('Responsible for overseeing task completion'),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Checkpoints')
                    ->schema([
                        Forms\Components\Repeater::make('checkpoints')
                            ->relationship('checkpoints')
                            ->schema([
                                Forms\Components\TextInput::make('title')
                                    ->required(),
                                Forms\Components\Textarea::make('description')
                                    ->rows(2),
                            ])
                            ->orderable()
                            ->collapsible(),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'gray' => 'draft',
                        'blue' => 'pending',
                        'warning' => 'in_progress',
                        'purple' => 'review',
                        'success' => 'completed',
                        'danger' => 'cancelled',
                    ]),
                
                Tables\Columns\BadgeColumn::make('priority')
                    ->colors([
                        'gray' => 'low',
                        'info' => 'medium',
                        'warning' => 'high',
                        'danger' => 'urgent',
                    ]),
                
                Tables\Columns\TextColumn::make('type')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('assignee.name')
                    ->label('Assignee')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('controller.name')
                    ->label('Controller')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('due_date')
                    ->dateTime()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('progress')
                    ->label('Progress')
                    ->formatStateUsing(fn ($record) => round($record->progress) . '%')
                    ->sortable(),
                
                Tables\Columns\IconColumn::make('is_overdue')
                    ->label('Overdue')
                    ->boolean()
                    ->trueIcon('heroicon-o-exclamation-triangle')
                    ->falseIcon('heroicon-o-check-circle')
                    ->trueColor('danger')
                    ->falseColor('success'),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'pending' => 'Pending',
                        'in_progress' => 'In Progress',
                        'review' => 'Review',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ]),
                
                Tables\Filters\SelectFilter::make('priority')
                    ->options([
                        'low' => 'Low',
                        'medium' => 'Medium',
                        'high' => 'High',
                        'urgent' => 'Urgent',
                    ]),
                
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'notification' => 'Notification',
                        'campaign' => 'Campaign',
                        'report' => 'Report',
                        'review' => 'Review',
                        'other' => 'Other',
                    ]),
                
                Tables\Filters\Filter::make('overdue')
                    ->query(fn (Builder $query): Builder => $query->overdue())
                    ->label('Overdue Only'),
                
                Tables\Filters\Filter::make('due_soon')
                    ->query(fn (Builder $query): Builder => $query->dueSoon())
                    ->label('Due Soon (3 days)'),
                
                Tables\Filters\Filter::make('due_date')
                    ->form([
                        Forms\Components\DatePicker::make('from'),
                        Forms\Components\DatePicker::make('until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('due_date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('due_date', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('mark_complete')
                    ->label('Mark Complete')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (InternalTask $record) {
                        $record->markAsCompleted();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('mark_complete')
                        ->label('Mark Complete')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->markAsCompleted();
                            }
                        }),
                ]),
            ])
            ->defaultSort('due_date', 'asc');
    }

    public static function getRelations(): array
    {
        return [
            'checkpoints',
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInternalTasks::route('/'),
            'create' => Pages\CreateInternalTask::route('/create'),
            'view' => Pages\ViewInternalTask::route('/{record}'),
            'edit' => Pages\EditInternalTask::route('/{record}/edit'),
        ];
    }
}
