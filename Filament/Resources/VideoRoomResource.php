<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\VideoRoomResource\Pages;
use App\Models\Video\VideoRoom;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

final class VideoRoomResource extends Resource
{
    protected static ?string $model = VideoRoom::class;
    protected static ?string $navigationIcon = 'heroicon-o-video-camera';
    protected static ?string $navigationGroup = 'Media & CDN';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Room Information')
                    ->schema([
                        Forms\Components\TextInput::make('room_name')
                            ->label('Room Name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('type')
                            ->label('Room Type')
                            ->options([
                                'consultation' => 'Consultation',
                                'grooming_demo' => 'Grooming Demo',
                                'training' => 'Training',
                                'meeting' => 'Meeting',
                            ])
                            ->required(),
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'scheduled' => 'Scheduled',
                                'active' => 'Active',
                                'ended' => 'Ended',
                                'cancelled' => 'Cancelled',
                            ])
                            ->required(),
                        Forms\Components\DateTimePicker::make('scheduled_at')
                            ->label('Scheduled At'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Recording Settings')
                    ->schema([
                        Forms\Components\Toggle::make('recording_enabled')
                            ->label('Enable Recording')
                            ->helperText('Recording requires explicit consent from all participants'),
                        Forms\Components\TextInput::make('recording_consent_signature')
                            ->label('Consent Signature')
                            ->visible(fn (Forms\Get $get) => $get('recording_enabled')),
                        Forms\Components\DateTimePicker::make('recording_consent_given_at')
                            ->label('Consent Given At')
                            ->disabled(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Access Control')
                    ->schema([
                        Forms\Components\TextInput::make('max_participants')
                            ->label('Max Participants')
                            ->numeric()
                            ->default(10),
                        Forms\Components\Toggle::make('is_public')
                            ->label('Public Room')
                            ->helperText('Public rooms can be accessed without authentication'),
                        Forms\Components\Toggle::make('require_password')
                            ->label('Require Password'),
                        Forms\Components\TextInput::make('room_password')
                            ->label('Room Password')
                            ->password()
                            ->visible(fn (Forms\Get $get) => $get('require_password')),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('room_name')
                    ->label('Room Name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'scheduled' => 'warning',
                        'active' => 'success',
                        'ended' => 'gray',
                        'cancelled' => 'danger',
                    }),
                Tables\Columns\TextColumn::make('scheduled_at')
                    ->label('Scheduled At')
                    ->dateTime(),
                Tables\Columns\TextColumn::make('participants_count')
                    ->label('Participants')
                    ->counts('participants')
                    ->sortable(),
                Tables\Columns\IconColumn::make('recording_enabled')
                    ->label('Recording')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'scheduled' => 'Scheduled',
                        'active' => 'Active',
                        'ended' => 'Ended',
                        'cancelled' => 'Cancelled',
                    ]),
                Tables\Filters\SelectFilter::make('type')
                    ->label('Type')
                    ->options([
                        'consultation' => 'Consultation',
                        'grooming_demo' => 'Grooming Demo',
                        'training' => 'Training',
                        'meeting' => 'Meeting',
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
            'index' => Pages\ListVideoRooms::route('/'),
            'create' => Pages\CreateVideoRoom::route('/create'),
            'view' => Pages\ViewVideoRoom::route('/{record}'),
            'edit' => Pages\EditVideoRoom::route('/{record}/edit'),
        ];
    }
}
