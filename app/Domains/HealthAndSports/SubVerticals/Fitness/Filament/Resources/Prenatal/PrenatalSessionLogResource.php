<?php

declare(strict_types=1);

namespace Modules\Fitness\Filament\Resources\Prenatal;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Fitness\Infrastructure\Models\Prenatal\PrenatalSessionLogModel;

final class PrenatalSessionLogResource extends Resource
{
    protected static ?string $model = PrenatalSessionLogModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Fitness - Prenatal';

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
                        
                        Forms\Components\TextInput::make('exercise_type')
                            ->maxLength(255)
                            ->label('Exercise Type'),
                        
                        Forms\Components\TextInput::make('duration_minutes')
                            ->numeric()
                            ->minValue(0)
                            ->suffix('min')
                            ->label('Duration'),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Vitals')
                    ->schema([
                        Forms\Components\TextInput::make('heart_rate_before')
                            ->numeric()
                            ->suffix('bpm')
                            ->label('Heart Rate Before'),
                        
                        Forms\Components\TextInput::make('heart_rate_after')
                            ->numeric()
                            ->suffix('bpm')
                            ->label('Heart Rate After'),
                        
                        Forms\Components\TextInput::make('blood_pressure_systolic')
                            ->numeric()
                            ->suffix('mmHg')
                            ->label('BP Systolic'),
                        
                        Forms\Components\TextInput::make('blood_pressure_diastolic')
                            ->numeric()
                            ->suffix('mmHg')
                            ->label('BP Diastolic'),
                    ])
                    ->columns(4),
                
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
                
                Tables\Columns\TextColumn::make('exercise_type')
                    ->searchable()
                    ->label('Exercise'),
                
                Tables\Columns\TextColumn::make('duration_minutes')
                    ->numeric()
                    ->suffix('min')
                    ->label('Duration'),
                
                Tables\Columns\TextColumn::make('heart_rate_after')
                    ->numeric()
                    ->suffix('bpm')
                    ->label('HR After')
                    ->color(fn ($state) => $state > 140 ? 'danger' : 'success'),
                
                Tables\Columns\TextColumn::make('blood_pressure_systolic')
                    ->numeric()
                    ->suffix('mmHg')
                    ->label('BP Systolic')
                    ->color(fn ($state) => $state > 140 ? 'danger' : 'success'),
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
            'index' => \Modules\Fitness\Filament\Resources\Prenatal\Pages\ListPrenatalSessionLogs::route('/'),
            'create' => \Modules\Fitness\Filament\Resources\Prenatal\Pages\CreatePrenatalSessionLog::route('/create'),
            'edit' => \Modules\Fitness\Filament\Resources\Prenatal\Pages\EditPrenatalSessionLog::route('/{record}/edit'),
        ];
    }
}
