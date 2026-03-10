<?php

namespace App\Filament\Tenant\Resources;

use App\Filament\Tenant\Resources\AppointmentResource\Pages;
use Modules\BeautyMasters\Models\Appointment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components;
use Filament\Tables\Columns;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AppointmentResource extends Resource
{
    protected static ?string $model = Appointment::class;

    protected static ?string $navigationIcon = 'heroicon-o-scissors';
    protected static ?string $navigationGroup = 'Beauty Salon';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Components\Section::make('Appointment Details')
                    ->schema([
                        Components\Select::make('master_id')
                            ->relationship('master', 'name')
                            ->required(),
                        Components\TextInput::make('service_name')
                            ->required(),
                        Components\TextInput::make('client_name')
                            ->required(),
                        
                        // Защита контактов: Мастер видит маску, Админ видит полностью
                        Components\TextInput::make('client_phone')
                            ->label('Client Phone')
                            ->mask('+7 (999) 999-99-99')
                            ->placeholder('+7 (___) ___-__-__')
                            ->formatStateUsing(fn ($state) => 
                                auth()->user()->can('view_client_contacts') 
                                ? $state 
                                : substr($state, 0, 4) . '***' . substr($state, -4)
                            )
                            ->disabled(fn () => !auth()->user()->can('view_client_contacts'))
                            ->required(),

                        Components\DateTimePicker::make('start_time')
                            ->required(),
                        Components\DateTimePicker::make('end_time')
                            ->required(),
                        Components\Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'confirmed' => 'Confirmed',
                                'completed' => 'Completed',
                                'cancelled' => 'Cancelled',
                            ])
                            ->default('pending'),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Columns\TextColumn::make('master.name')
                    ->sortable()
                    ->searchable(),
                Columns\TextColumn::make('service_name')
                    ->searchable(),
                Columns\TextColumn::make('client_name')
                    ->sortable()
                    ->searchable(),
                
                // В таблице телефон маскируется для мастеров
                Columns\TextColumn::make('client_phone')
                    ->label('Phone')
                    ->getStateUsing(fn (Model $record) => 
                        auth()->user()->can('view_client_contacts') 
                        ? $record->client_phone 
                        : substr($record->client_phone, 0, 4) . '***' . substr($record->client_phone, -2)
                    ),

                Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'confirmed',
                        'primary' => 'completed',
                        'danger' => 'cancelled',
                    ]),
                Columns\TextColumn::make('start_time')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'confirmed' => 'Confirmed',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ]),
            ])
            ->actions([
                \App\Filament\Tenant\Resources\Common\VideoCallAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn () => auth()->user()->can('delete_appointments')),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageAppointments::route('/'),
        ];
    }
}
