<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Psychology;


use Psr\Log\LoggerInterface;
final class BookingResource extends Resource
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}


    protected static ?string $model = PsychologicalBooking::class;

        protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
        protected static ?string $navigationGroup = 'Psychological Services';
        protected static ?int $navigationSort = 2;

        public static function form(Form $form): Form
        {
            return $form->schema([
                Forms\Components\Section::make('Booking Details')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('client_id')
                            ->relationship('client', 'name')
                            ->required()
                            ->searchable(),
                        Forms\Components\Select::make('psychologist_id')
                            ->relationship('psychologist', 'full_name')
                            ->required()
                            ->searchable(),
                        Forms\Components\Select::make('service_id')
                            ->relationship('service', 'name')
                            ->required(),
                        Forms\Components\DateTimePicker::make('scheduled_at')
                            ->required()
                            ->minDate(now()),
                        Forms\Components\TextInput::make('price_at_booking')
                            ->numeric()
                            ->prefix('RUB')
                            ->required(),
                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => 'Pending Confirmation',
                                'confirmed' => 'Confirmed',
                                'completed' => 'Completed',
                                'cancelled' => 'Cancelled',
                            ])
                            ->required()
                            ->native(false),
                        Forms\Components\Textarea::make('client_notes')
                            ->columnSpanFull()
                            ->rows(3),
                    ]),
            ]);
        }

        public static function table(Table $table): Table
        {
            return $table
                ->columns([
                    Tables\Columns\TextColumn::make('scheduled_at')
                        ->dateTime()
                        ->sortable()
                        ->color('primary')
                        ->weight('bold'),
                    Tables\Columns\TextColumn::make('client.name')
                        ->searchable()
                        ->sortable(),
                    Tables\Columns\TextColumn::make('psychologist.full_name')
                        ->searchable()
                        ->sortable(),
                    Tables\Columns\SelectColumn::make('status')
                        ->options([
                            'pending' => 'Pending',
                            'confirmed' => 'Confirmed',
                            'completed' => 'Completed',
                            'cancelled' => 'Cancelled',
                        ])
                        ->sortable(),
                    Tables\Columns\TextColumn::make('price_at_booking')
                        ->money('RUB')
                        ->sortable(),
                ])
                ->filters([
                    Tables\Filters\SelectFilter::make('status'),
                    Tables\Filters\Filter::make('upcoming')
                        ->query(fn (Builder $query) => $query->where('scheduled_at', '>=', now())),
                ])
                ->actions([
                    Tables\Actions\Action::make('startSession')
                        ->label('Open Session')
                        ->icon('heroicon-o-presentation-chart-line')
                        ->color('success')
                        ->visible(fn (PsychologicalBooking $record) => $record->status === 'confirmed')
                        ->action(function (PsychologicalBooking $record) {
                            $this->logger->info('Filament manual session start', [
                                'booking_id' => $record->id,
                            ]);
                            // Logic handled by PsychologicalService ideally
                        }),
                    Tables\Actions\EditAction::make(),
                ])
                ->defaultSort('scheduled_at', 'desc');
        }

        public static function getPages(): array
        {
            return [
                'index' => \App\Filament\Tenant\Resources\Psychology\BookingResource\Pages\ListBookings::route('/'),
                'create' => \App\Filament\Tenant\Resources\Psychology\BookingResource\Pages\CreateBooking::route('/create'),
            ];
        }
}
