<?php declare(strict_types=1);

namespace App\Domains\Beauty\Filament;

use App\Domains\Beauty\Models\Appointment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

final class AppointmentResource extends Resource
{
    protected static ?string $model = Appointment::class;
    protected static ?string $navigationIcon = 'heroicon-o-calendar';
    protected static ?string $navigationGroup = 'Beauty';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Appointment Details')
                    ->schema([
                        Forms\Components\Select::make('salon_id')
                            ->relationship('salon', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('master_id')
                            ->relationship('master', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('service_id')
                            ->relationship('service', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('user_id')
                            ->relationship('user', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\DateTimePicker::make('starts_at')
                            ->required()
                            ->minDate(now()),
                        Forms\Components\DateTimePicker::make('ends_at')
                            ->required()
                            ->minDate(now()),
                        Forms\Components\TextInput::make('total_price')
                            ->required()
                            ->numeric()
                            ->prefix('₽')
                            ->step(0.01),
                        Forms\Components\Toggle::make('is_b2b')
                            ->label('B2B Booking'),
                        Forms\Components\Select::make('status')
                            ->options([
                                'pending_payment' => 'Pending Payment',
                                'confirmed' => 'Confirmed',
                                'in_progress' => 'In Progress',
                                'completed' => 'Completed',
                                'cancelled' => 'Cancelled',
                                'no_show' => 'No Show',
                            ])
                            ->required()
                            ->default('pending_payment'),
                        Forms\Components\Textarea::make('cancellation_reason')
                            ->rows(3)
                            ->visible(fn (Forms\Get $get): bool => $get('status') === 'cancelled'),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Pricing & Metadata')
                    ->schema([
                        Forms\Components\KeyValue::make('metadata')
                            ->keyLabel('Field')
                            ->valueLabel('Value')
                            ->editable(),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('uuid')
                    ->label('UUID')
                    ->searchable()
                    ->toggleable()
                    ->toggledHiddenByDefault(),
                Tables\Columns\TextColumn::make('salon.name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('master.name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('service.name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('starts_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('ends_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('total_price')
                    ->money('RUB')
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending_payment',
                        'success' => 'confirmed',
                        'info' => 'in_progress',
                        'primary' => 'completed',
                        'danger' => 'cancelled',
                        'gray' => 'no_show',
                    ]),
                Tables\Columns\IconColumn::make('is_b2b')
                    ->boolean()
                    ->label('B2B'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable()
                    ->toggledHiddenByDefault(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending_payment' => 'Pending Payment',
                        'confirmed' => 'Confirmed',
                        'in_progress' => 'In Progress',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                        'no_show' => 'No Show',
                    ]),
                Tables\Filters\SelectFilter::make('salon')
                    ->relationship('salon', 'name'),
                Tables\Filters\SelectFilter::make('master')
                    ->relationship('master', 'name'),
                Tables\Filters\Filter::make('is_b2b')
                    ->query(fn (Builder $query): Builder => $query->where('is_b2b', true))
                    ->label('B2B Only'),
                Tables\Filters\Filter::make('date_range')
                    ->form([
                        Forms\Components\DatePicker::make('from'),
                        Forms\Components\DatePicker::make('until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('starts_at', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('starts_at', '<=', $date),
                            );
                    }),
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
            ])
            ->defaultSort('starts_at', 'desc');
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
            'index' => \App\Domains\Beauty\Filament\AppointmentResource\Pages\ListAppointments::route('/'),
            'create' => \App\Domains\Beauty\Filament\AppointmentResource\Pages\CreateAppointment::route('/create'),
            'view' => \App\Domains\Beauty\Filament\AppointmentResource\Pages\ViewAppointment::route('/{record}'),
            'edit' => \App\Domains\Beauty\Filament\AppointmentResource\Pages\EditAppointment::route('/{record}/edit'),
        ];
    }
}
