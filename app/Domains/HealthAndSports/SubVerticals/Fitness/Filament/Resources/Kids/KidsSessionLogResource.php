<?php

declare(strict_types=1);

namespace Modules\Fitness\Filament\Resources\Kids;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Fitness\Infrastructure\Models\Kids\KidsSessionLogModel;

final class KidsSessionLogResource extends Resource
{
    protected static ?string $model = KidsSessionLogModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Fitness - Kids';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Session Details')
                    ->schema([
                        Forms\Components\Select::make('enrollment_id')
                            ->relationship('enrollment', 'id')
                            ->searchable()
                            ->required()
                            ->label('Enrollment'),
                        
                        Forms\Components\DatePicker::make('session_date')
                            ->required()
                            ->label('Session Date'),
                        
                        Forms\Components\TextInput::make('activity_type')
                            ->maxLength(255)
                            ->label('Activity Type'),
                        
                        Forms\Components\TextInput::make('duration_minutes')
                            ->numeric()
                            ->minValue(0)
                            ->suffix('min')
                            ->label('Duration'),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Mood & Feedback')
                    ->schema([
                        Forms\Components\Select::make('mood')
                            ->options([
                                'happy' => 'Happy',
                                'excited' => 'Excited',
                                'calm' => 'Calm',
                                'tired' => 'Tired',
                                'frustrated' => 'Frustrated',
                            ])
                            ->label('Child Mood'),
                    ]),
                
                Forms\Components\Section::make('Notes')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->maxLength(65535)
                            ->label('Notes')
                            ->rows(3),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('session_date')
                    ->date()
                    ->sortable()
                    ->label('Date'),
                
                Tables\Columns\TextColumn::make('enrollment.client.first_name')
                    ->searchable()
                    ->label('Client'),
                
                Tables\Columns\TextColumn::make('activity_type')
                    ->searchable()
                    ->label('Activity'),
                
                Tables\Columns\TextColumn::make('duration_minutes')
                    ->numeric()
                    ->suffix('min')
                    ->label('Duration'),
                
                Tables\Columns\TextColumn::make('mood')
                    ->badge()
                    ->color(fn (string $state): string => match($state) {
                        'happy' => 'success',
                        'excited' => 'primary',
                        'calm' => 'info',
                        'tired' => 'warning',
                        'frustrated' => 'danger',
                        default => 'gray',
                    })
                    ->label('Mood'),
            ])
            ->filters([
                Tables\Filters\Filter::make('date_range')
                    ->form([
                        Forms\Components\DatePicker::make('from'),
                        Forms\Components\DatePicker::make('until'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when(
                                $data['from'],
                                fn ($query) => $query->where('session_date', '>=', $data['from'])
                            )
                            ->when(
                                $data['until'],
                                fn ($query) => $query->where('session_date', '<=', $data['until'])
                            );
                    }),
            ])
            ->actions([
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
            'index' => \Modules\Fitness\Filament\Resources\Kids\Pages\ListKidsSessionLogs::route('/'),
            'create' => \Modules\Fitness\Filament\Resources\Kids\Pages\CreateKidsSessionLog::route('/create'),
            'edit' => \Modules\Fitness\Filament\Resources\Kids\Pages\EditKidsSessionLog::route('/{record}/edit'),
        ];
    }
}
