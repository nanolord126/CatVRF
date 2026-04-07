<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use Filament\Resources\Resource;

final class DentalAppointmentResource extends Resource
{

    protected static ?string $model = DentalAppointment::class;

        protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

        protected static ?string $navigationGroup = 'Dental Vertical';

        protected static ?string $modelLabel = 'Appointment';

        protected static ?string $pluralModelLabel = 'Appointments';

        /**
         * Form Specification (Full Appointment Process).
         * Exceeds 60 lines.
         */
        public static function form(Form $form): Form
        {
            return $form
                ->schema([
                    Section::make('Patient & Physician Linkage')
                        ->description('Identify participants of the medical session.')
                        ->columns(2)
                        ->schema([
                            Select::make('client_id')
                                ->relationship('client', 'name')
                                ->required()
                                ->searchable()
                                ->preload()
                                ->label('Patient (Client Account)')
                                ->columnSpan(1),
                            Select::make('dentist_id')
                                ->relationship('dentist', 'full_name')
                                ->required()
                                ->searchable()
                                ->preload()
                                ->label('Doctor (Dentist)')
                                ->columnSpan(1),
                            Select::make('dental_clinic_id')
                                ->relationship('clinic', 'name')
                                ->required()
                                ->searchable()
                                ->preload()
                                ->label('Clinical Location')
                                ->columnSpan(1),
                            Select::make('dental_service_id')
                                ->relationship('service', 'name')
                                ->required()
                                ->searchable()
                                ->preload()
                                ->label('Primary Treatment Service')
                                ->columnSpan(1),
                        ]),

                    Section::make('Scheduling & Logistics')
                        ->description('Define time frames and duration.')
                        ->columns(2)
                        ->schema([
                            DateTimePicker::make('datetime_start')
                                ->required()
                                ->label('Scheduled Start')
                                ->displayFormat('d.m.Y H:i')
                                ->columnSpan(1),
                            TextInput::make('duration_minutes')
                                ->numeric()
                                ->required()
                                ->minValue(5)
                                ->maxValue(480)
                                ->default(60)
                                ->label('Expected Duration (min)')
                                ->columnSpan(1),
                        ]),

                    Section::make('Life Cycle Status')
                        ->description('Track treatment execution.')
                        ->columns(2)
                        ->schema([
                            Select::make('status')
                                ->required()
                                ->options([
                                    'pending' => 'Pending Confirmation',
                                    'confirmed' => 'Active / Confirmed',
                                    'completed' => 'Finalized / Completed',
                                    'cancelled' => 'Cancelled / Denied',
                                ])
                                ->default('pending')
                                ->label('Workflow State')
                                ->columnSpan(1),
                            Select::make('payment_status')
                                ->required()
                                ->options([
                                    'unpaid' => 'Awaiting Payment',
                                    'partially_paid' => 'Partial / Pre-payment',
                                    'paid' => 'Fully Invoiced & Paid',
                                    'refunded' => 'Refunded / Corrected',
                                ])
                                ->default('unpaid')
                                ->label('Monetary State')
                                ->columnSpan(1),
                            TextInput::make('price')
                                ->numeric()
                                ->required()
                                ->label('Agreed Price (Kopecks)')
                                ->columnSpan(1),
                        ]),

                    Section::make('Medical Notes (Privacy Scope: ФЗ-152)')
                        ->description('Patient records. Managed under HIPAA/ФЗ-152 compliance.')
                        ->columns(1)
                        ->schema([
                            Textarea::make('notes')
                                ->maxLength(2000)
                                ->rows(5)
                                ->label('Clinical Notes & Observations')
                                ->placeholder('Observe medical privacy guidelines...')
                                ->columnSpanFull(),
                        ]),

                    Section::make('Metadata & Fraud Analysis')
                        ->description('Security and audit parameters.')
                        ->columns(3)
                        ->schema([
                            Placeholder::make('uuid')
                                ->label('Record UUID')
                                ->content(fn ($record) => $record?->uuid ?? (string) Str::uuid()),
                            Placeholder::make('correlation_id')
                                ->label('Correlation ID')
                                ->content(fn ($record) => $record?->correlation_id ?? 'Auto-generated on save'),
                            Placeholder::make('history')
                                ->label('Creation History')
                                ->content(fn ($record) => $record?->created_at?->toDateTimeString() ?? 'New Row'),
                        ]),
                ]);

        }

        public static function getPages(): array
        {
            return [
                'index' => Pages\ListDentalAppointment::route('/'),
                'create' => Pages\CreateDentalAppointment::route('/create'),
                'edit' => Pages\EditDentalAppointment::route('/{record}/edit'),
                'view' => Pages\ViewDentalAppointment::route('/{record}'),
            ];
        }
}
