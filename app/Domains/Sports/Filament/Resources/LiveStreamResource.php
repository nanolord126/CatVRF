<?php

declare(strict_types=1);

namespace App\Domains\Sports\Filament\Resources;

use App\Domains\Sports\Filament\Resources\LiveStreamResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class LiveStreamResource extends Resource
{
    protected static ?string $model = \App\Domains\Sports\Models\LiveStream::class;

    protected static ?string $navigationIcon = 'heroicon-o-video-camera';
    protected static ?string $navigationGroup = 'Sports';
    protected static ?int $navigationSort = 11;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Stream Information')
                    ->schema([
                        Forms\Components\TextInput::make('session_title')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('session_description')
                            ->maxLength(2000)
                            ->rows(3),
                        Forms\Components\Select::make('trainer_id')
                            ->relationship('trainer', 'name')
                            ->required(),
                        Forms\Components\Select::make('stream_type')
                            ->options([
                                'group' => 'Group',
                                'personal' => 'Personal',
                                'workshop' => 'Workshop',
                            ])
                            ->required(),
                        Forms\Components\DateTimePicker::make('scheduled_start')
                            ->required(),
                        Forms\Components\DateTimePicker::make('scheduled_end')
                            ->required()
                            ->after('scheduled_start'),
                        Forms\Components\TextInput::make('max_participants')
                            ->numeric()
                            ->default(50)
                            ->maxValue(100),
                        Forms\Components\Select::make('status')
                            ->options([
                                'scheduled' => 'Scheduled',
                                'live' => 'Live',
                                'ended' => 'Ended',
                                'cancelled' => 'Cancelled',
                            ])
                            ->required(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable(),
                Tables\Columns\TextColumn::make('session_title')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('trainer.name')
                    ->label('Trainer')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('stream_type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'group' => 'primary',
                        'personal' => 'success',
                        'workshop' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'scheduled' => 'info',
                        'live' => 'success',
                        'ended' => 'gray',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('scheduled_start')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('current_participants')
                    ->label('Participants')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'scheduled' => 'Scheduled',
                        'live' => 'Live',
                        'ended' => 'Ended',
                        'cancelled' => 'Cancelled',
                    ]),
                Tables\Filters\SelectFilter::make('stream_type')
                    ->options([
                        'group' => 'Group',
                        'personal' => 'Personal',
                        'workshop' => 'Workshop',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListLiveStreams::route('/'),
            'create' => Pages\CreateLiveStream::route('/create'),
            'view' => Pages\ViewLiveStream::route('/{record}'),
            'edit' => Pages\EditLiveStream::route('/{record}/edit'),
        ];
    }
}
