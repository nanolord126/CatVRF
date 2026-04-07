<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Medical;

use Filament\Resources\Resource;

final class AppointmentResource extends Resource
{

    protected static ?string $model = Appointment::class;

        protected static ?string $navigationIcon = 'heroicon-o-calendar';

        protected static ?string $navigationGroup = 'Medical';

        public static function form(Forms\Form $form): Forms\Form
        {
            return $form->schema([
                Forms\Components\Grid::make(2)->schema([
                    Forms\Components\Select::make('doctor_id')
                        ->relationship('doctor', 'full_name')
                        ->required(),

                    Forms\Components\Select::make('clinic_id')
                        ->relationship('clinic', 'name')
                        ->required(),

                    Forms\Components\DateTimePicker::make('appointment_date')
                        ->required(),

                    Forms\Components\Select::make('status')
                        ->options([
                            'pending' => 'Pending',
                            'confirmed' => 'Confirmed',
                            'completed' => 'Completed',
                            'cancelled' => 'Cancelled',
                        ])
                        ->required(),

                    Forms\Components\Textarea::make('reason')
                        ->columnSpanFull(),

                    Forms\Components\Textarea::make('notes')
                        ->columnSpanFull(),
                ]),
            ]);
        }

        public static function table(Tables\Table $table): Tables\Table
        {
            return $table
                ->columns([
                    Tables\Columns\TextColumn::make('doctor.full_name')
                        ->sortable()
                        ->searchable(),

                    Tables\Columns\TextColumn::make('clinic.name')
                        ->sortable(),

                    Tables\Columns\TextColumn::make('appointment_date')
                        ->dateTime()
                        ->sortable(),

                    Tables\Columns\TextColumn::make('status')
                        ->badge(),

                    Tables\Columns\TextColumn::make('created_at')
                        ->dateTime(),
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
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
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
                'index' => \App\Filament\Tenant\Resources\Medical\AppointmentResource\Pages\ListAppointments::route('/'),
                'create' => \App\Filament\Tenant\Resources\Medical\AppointmentResource\Pages\CreateAppointment::route('/create'),
                'view' => \App\Filament\Tenant\Resources\Medical\AppointmentResource\Pages\ViewAppointment::route('/{record}'),
                'edit' => \App\Filament\Tenant\Resources\Medical\AppointmentResource\Pages\EditAppointment::route('/{record}/edit'),
            ];
        }

        protected static function getEloquentQuery(): Builder
        {
            return parent::getEloquentQuery()
                ->where('tenant_id', filament()->getTenant()->id)
                ->with(['doctor', 'clinic']);
        }
}
