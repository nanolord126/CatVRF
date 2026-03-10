<?php

namespace App\Filament\Tenant\Resources;

use App\Filament\Tenant\Resources\StaffScheduleResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Staff\Models\StaffSchedule;
use Illuminate\Database\Eloquent\Builder;

class StaffScheduleResource extends Resource
{
    protected static ?string $model = StaffSchedule::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    
    protected static ?string $navigationGroup = 'HR Management';
    protected static ?string $label = 'Shift';
    protected static ?string $pluralLabel = 'Shifts';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->label('Staff Member')
                    ->relationship('user', 'name')
                    ->required()
                    ->searchable(),
                Forms\Components\DatePicker::make('date')
                    ->required()
                    ->native(false),
                Forms\Components\TimePicker::make('start_time')
                    ->required()
                    ->seconds(false),
                Forms\Components\TimePicker::make('end_time')
                    ->required()
                    ->seconds(false),
                Forms\Components\Select::make('shift_type')
                    ->options([
                        'regular' => 'Regular',
                        'day' => 'Day Shift',
                        'night' => 'Night Shift',
                        'flexible' => 'Flexible',
                    ])
                    ->default('regular')
                    ->required(),
                Forms\Components\Select::make('status')
                    ->options([
                        'scheduled' => 'Scheduled',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ])
                    ->default('scheduled')
                    ->required(),
                Forms\Components\Hidden::make('correlation_id')
                    ->default(fn () => (string) \Illuminate\Support\Str::uuid()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Staff Member')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('start_time')
                    ->time('H:i'),
                Tables\Columns\TextColumn::make('end_time')
                    ->time('H:i'),
                Tables\Columns\BadgeColumn::make('shift_type')
                    ->colors([
                        'primary' => 'day',
                        'secondary' => 'night',
                    ]),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'scheduled',
                        'success' => 'completed',
                        'danger' => 'cancelled',
                    ]),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('user')
                    ->relationship('user', 'name', fn (Builder $query) => $query->where('is_active', true)),
                Tables\Filters\Filter::make('date')
                    ->form([
                        Forms\Components\DatePicker::make('from'),
                        Forms\Components\DatePicker::make('until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('date', '<=', $date),
                            );
                    })
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
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStaffSchedules::route('/'),
            'create' => Pages\CreateStaffSchedule::route('/create'),
            'edit' => Pages\EditStaffSchedule::route('/{record}/edit'),
        ];
    }
}
