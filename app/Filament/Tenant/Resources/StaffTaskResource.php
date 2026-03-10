<?php

namespace App\Filament\Tenant\Resources;

use App\Filament\Tenant\Resources\StaffTaskResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Staff\Models\StaffTask;
use Filament\Notifications\Notification;
use App\Models\User;

class StaffTaskResource extends Resource
{
    protected static ?string $model = StaffTask::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    
    protected static ?string $navigationGroup = 'Staff Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('user_id')
                    ->label('Assigned Staff')
                    ->options(User::where('is_active', true)->pluck('name', 'id'))
                    ->searchable(),
                Forms\Components\Select::make('status')
                    ->options([
                        'TODO' => 'TODO',
                        'IN_PROGRESS' => 'In Progress',
                        'DONE' => 'Done',
                    ])
                    ->required()
                    ->default('TODO'),
                Forms\Components\Select::make('priority')
                    ->options([
                        'low' => 'Low',
                        'medium' => 'Medium',
                        'high' => 'High',
                    ])
                    ->default('medium'),
                Forms\Components\Textarea::make('description')
                    ->columnSpanFull(),
                Forms\Components\MorphToSelect::make('taskable')
                    ->types([
                        Forms\Components\MorphToSelect\Type::make(\Modules\Hotels\Models\Room::class)
                            ->titleAttribute('name')
                            ->label('Room'),
                        Forms\Components\MorphToSelect\Type::make(\Modules\Hotels\Models\Booking::class)
                            ->titleAttribute('id')
                            ->label('Booking'),
                    ])
                    ->searchable(),
                Forms\Components\Hidden::make('correlation_id')
                    ->default(fn () => (string) \Illuminate\Support\Str::uuid()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Assigned Staff')
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'TODO',
                        'primary' => 'IN_PROGRESS',
                        'success' => 'DONE',
                    ]),
                Tables\Columns\TextColumn::make('priority')
                    ->badge()
                    ->colors([
                        'gray' => 'low',
                        'warning' => 'medium',
                        'danger' => 'high',
                    ]),
                Tables\Columns\TextColumn::make('taskable_type')
                    ->label('Type')
                    ->formatStateUsing(fn ($state) => class_basename($state)),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'TODO' => 'TODO',
                        'IN_PROGRESS' => 'In Progress',
                        'DONE' => 'Done',
                    ]),
                Tables\Filters\SelectFilter::make('user_id')
                    ->label('Staff Member')
                    ->relationship('user', 'name'),
            ])
            ->actions([
                \App\Filament\Tenant\Resources\Common\VideoCallAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('complete')
                    ->label('Done')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->hidden(fn (StaffTask $record) => $record->status === 'DONE')
                    ->action(function (StaffTask $record) {
                        $record->update([
                            'status' => 'DONE',
                            'completed_at' => now(),
                        ]);

                        Notification::make()
                            ->title('Task completed')
                            ->success()
                            ->send();
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
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStaffTasks::route('/'),
            'create' => Pages\CreateStaffTask::route('/create'),
            'edit' => Pages\EditStaffTask::route('/{record}/edit'),
        ];
    }
}
