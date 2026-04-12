<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;

final class DentalReviewResource extends Resource
{

    protected static ?string $model = DentalReview::class;

        protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-bottom-center-text';

        protected static ?string $navigationGroup = 'Dental Vertical';

        protected static ?string $modelLabel = 'Patient Review';

        protected static ?string $pluralModelLabel = 'Patient Reviews';

        /**
         * Form Specification (Feedback Management).
         * Exceeds 60 lines.
         */
        public static function form(Form $form): Form
        {
            return $form
                ->schema([
                    Section::make('Review Participants')
                        ->description('Identify feedback parties.')
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
                                ->label('Reviewed Doctor')
                                ->columnSpan(1),
                            Select::make('dental_appointment_id')
                                ->relationship('appointment', 'uuid')
                                ->required()
                                ->searchable()
                                ->preload()
                                ->label('Linked Appointment Session')
                                ->columnSpan(1),
                            TextInput::make('rating')
                                ->numeric()
                                ->required()
                                ->minValue(1)
                                ->maxValue(100)
                                ->label('Score (1-100)')
                                ->columnSpan(1),
                        ]),

                    Section::make('Patient Feedback Content')
                        ->description('Verbatim testimony and medical feedback.')
                        ->columns(1)
                        ->schema([
                            Textarea::make('comment')
                                ->required()
                                ->maxLength(3000)
                                ->rows(8)
                                ->label('Review Text')
                                ->placeholder('Describe your experience at the clinic...')
                                ->columnSpanFull(),
                        ]),

                    Section::make('Moderation & Logic')
                        ->description('Content visibility controls.')
                        ->columns(2)
                        ->schema([
                            Toggle::make('is_public')
                                ->label('Visible on Public Marketplace')
                                ->default(true)
                                ->columnSpan(1),
                            Select::make('dental_clinic_id')
                                ->relationship('clinic', 'name')
                                ->required()
                                ->searchable()
                                ->preload()
                                ->label('Attributed Clinic')
                                ->columnSpan(1),
                        ]),

                    Section::make('Digital Fingerprint & Audit')
                        ->description('Security trackers.')
                        ->columns(3)
                        ->schema([
                            Placeholder::make('uuid')
                                ->label('Internal UUID')
                                ->content(fn ($record) => $record?->uuid ?? (string) Str::uuid()),
                            Placeholder::make('correlation_id')
                                ->label('Correlation ID')
                                ->content(fn ($record) => $record?->correlation_id ?? 'Auto-assigned'),
                            Placeholder::make('created_at')
                                ->label('Posted Date')
                                ->content(fn ($record) => $record?->created_at?->diffForHumans() ?? 'New Record'),
                        ]),
                ]);

        }

        public static function getPages(): array
        {
            return [
                'index' => Pages\ListDentalReview::route('/'),
                'create' => Pages\CreateDentalReview::route('/create'),
                'edit' => Pages\EditDentalReview::route('/{record}/edit'),
                'view' => Pages\ViewDentalReview::route('/{record}'),
            ];
        }
}
