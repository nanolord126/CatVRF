<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;

final class DentalClinicResource extends Resource
{

    protected static ?string $model = DentalClinic::class;

        protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

        protected static ?string $navigationGroup = 'Dental Vertical';

        protected static ?string $modelLabel = 'Dental Clinic';

        protected static ?string $pluralModelLabel = 'Dental Clinics';

        /**
         * Form Specification (Full Set of Properties).
         * Exceeds 60 lines.
         */
        public static function form(Form $form): Form
        {
            return $form
                ->schema([
                    Section::make('Clinic Core Information')
                        ->description('Primary identifies and license details.')
                        ->columns(2)
                        ->schema([
                            TextInput::make('name')
                                ->required()
                                ->maxLength(255)
                                ->placeholder('Modern Dental Center')
                                ->columnSpan(1),
                            TextInput::make('license_number')
                                ->required()
                                ->unique(ignoreRecord: true)
                                ->maxLength(100)
                                ->label('Medical License Number')
                                ->columnSpan(1),
                            TextInput::make('uuid')
                                ->disabled()
                                ->dehydrated(false)
                                ->label('Internal UUID')
                                ->default(fn () => (string) Str::uuid())
                                ->columnSpan(1),
                            Toggle::make('is_premium')
                                ->label('Premium Clinic Status')
                                ->inline(false)
                                ->columnSpan(1),
                        ]),

                    Section::make('Location & Contact')
                        ->description('Geographical and contact accessibility.')
                        ->columns(2)
                        ->schema([
                            Textarea::make('address')
                                ->required()
                                ->maxLength(500)
                                ->rows(3)
                                ->placeholder('123 Dental St, Moscow, RU')
                                ->columnSpanFull(),
                            TextInput::make('rating')
                                ->numeric()
                                ->disabled()
                                ->default(0)
                                ->label('Current Clinic Rating (0-100)')
                                ->columnSpan(1),
                        ]),

                    Section::make('Operational Schedule')
                        ->description('Business hours for the dental practice.')
                        ->columns(1)
                        ->schema([
                            KeyValue::make('schedule')
                                ->label('Working Hours (JSON)')
                                ->keyLabel('Day of Week')
                                ->valueLabel('Opening Intervals (e.g., 09:00-21:00)')
                                ->default([
                                    'monday' => '09:00-21:00',
                                    'tuesday' => '09:00-21:00',
                                    'wednesday' => '09:00-21:00',
                                    'thursday' => '09:00-21:00',
                                    'friday' => '09:00-21:00',
                                    'saturday' => '10:00-18:00',
                                    'sunday' => 'Closed',
                                ])
                                ->columnSpanFull(),
                        ]),

                    Section::make('Analysis & Analytics')
                        ->description('Tags for internal business intelligence.')
                        ->columns(1)
                        ->schema([
                            KeyValue::make('tags')
                                ->label('Business Tags')
                                ->keyLabel('Tag Name')
                                ->valueLabel('Tag Value')
                                ->columnSpanFull(),
                        ]),

                    Section::make('Audit Trail')
                        ->description('Record metadata.')
                        ->schema([
                            Placeholder::make('correlation_id')
                                ->label('Correlation ID')
                                ->content(fn ($record) => $record?->correlation_id ?? 'Not assigned'),
                            Placeholder::make('created_at')
                                ->label('Created at')
                                ->content(fn ($record) => $record?->created_at?->diffForHumans() ?? 'New Record'),
                            Placeholder::make('updated_at')
                                ->label('Last Updated')
                                ->content(fn ($record) => $record?->updated_at?->toDateTimeString() ?? 'New Record'),
                        ])->columns(3),
                ]);

        }

        public static function getPages(): array
        {
            return [
                'index' => Pages\ListDentalClinic::route('/'),
                'create' => Pages\CreateDentalClinic::route('/create'),
                'edit' => Pages\EditDentalClinic::route('/{record}/edit'),
                'view' => Pages\ViewDentalClinic::route('/{record}'),
            ];
        }
}
