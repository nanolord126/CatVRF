<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\WeddingPlanning;

use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;

final class WeddingBookingResource extends Resource
{

    protected static ?string $model = WeddingBooking::class;

        protected static ?string $navigationIcon = 'heroicon-o-book-open';

        protected static ?string $navigationGroup = 'Wedding Planning';

        public static function form(Form $form): Form
        {
            return $form->schema([
                Forms\Components\Section::make('Booking Details')
                    ->description('Link a wedding event to a vendor or a pre-defined package.')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Select::make('wedding_event_id')
                                    ->relationship('weddingEvent', 'title')
                                    ->label('Wedding Event')
                                    ->required()
                                    ->searchable(),
                                Forms\Components\Select::make('status')
                                    ->options([
                                        'pending' => 'Pending',
                                        'reserved' => 'Reserved',
                                        'paid_partial' => 'Paid Partial',
                                        'paid_full' => 'Paid Full',
                                        'cancelled' => 'Cancelled',
                                        'completed' => 'Completed',
                                    ])
                                    ->required()
                                    ->default('pending'),
                                Forms\Components\DateTimePicker::make('reserved_at')
                                    ->label('Reservation Time')
                                    ->required()
                                    ->displayFormat('d/m/Y H:i')
                                    ->placeholder('Select reservation date'),
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\MorphToSelect::make('bookable')
                                    ->label('Provider / Content')
                                    ->types([
                                        Forms\Components\MorphToSelect\Type::make(\App\Domains\WeddingPlanning\Models\WeddingVendor::class)
                                            ->titleAttribute('name')
                                            ->label('Vendor (Photographer, Venue, etc.)'),
                                        Forms\Components\MorphToSelect\Type::make(\App\Domains\WeddingPlanning\Models\WeddingPackage::class)
                                            ->titleAttribute('name')
                                            ->label('All-in-One Package'),
                                    ])
                                    ->required()
                                    ->searchable(),
                                Forms\Components\TextInput::make('idempotency_key')
                                    ->label('Payment Idempotency Key')
                                    ->disabled()
                                    ->placeholder('System generated on payment'),
                            ]),
                    ])
                    ->columnSpanFull(),

                // Financial Section (Kopecks handling)
                Forms\Components\Section::make('Financial Oversight')
                    ->schema([
                        Forms\Components\Grid::make(4)
                            ->schema([
                                Forms\Components\TextInput::make('amount')
                                    ->numeric()
                                    ->required()
                                    ->label('Total Amount (Kopecks)')
                                    ->suffix('RUB')
                                    ->placeholder('100 000 (1k RUB)'),
                                Forms\Components\TextInput::make('prepayment_amount')
                                    ->numeric()
                                    ->label('Prepayment (Kopecks)')
                                    ->suffix('RUB')
                                    ->placeholder('20 000 (100 RUB)'),
                                Forms\Components\DateTimePicker::make('paid_at')
                                    ->label('Full Payment Date'),
                                Forms\Components\Select::make('payment_status')
                                    ->options([
                                        'not_paid' => 'Not Paid',
                                        'deposit_paid' => 'Deposit Paid',
                                        'fully_paid' => 'Fully Paid',
                                        'refunded' => 'Refunded',
                                    ])
                                    ->default('not_paid')
                                    ->required(),
                            ]),
                    ])
                    ->columnSpanFull(),

                // Section: Meta-Data & Audit
                Forms\Components\Section::make('Audit Information')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('correlation_id')
                                    ->label('Correlation ID')
                                    ->disabled()
                                    ->placeholder('System generated'),
                                Forms\Components\TextInput::make('uuid')
                                    ->label('Booking Global UUID')
                                    ->disabled()
                                    ->placeholder('System generated'),
                                Forms\Components\TagsInput::make('tags')
                                    ->label('Internal Tags (VIP, Urgent, Refundable)'),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
        }

        public static function table(Table $table): Table
        {
            return $table->columns([
                Tables\Columns\TextColumn::make('weddingEvent.title')
                    ->label('Wedding')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'reserved' => 'info',
                        'paid_full' => 'success',
                        'cancelled' => 'danger',
                        default => 'info',
                    }),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Total (RUB)')
                    ->money('RUB')
                    ->formatStateUsing(fn ($state) => (float)$state / 100),
                Tables\Columns\TextColumn::make('prepayment_amount')
                    ->label('Prepayment')
                    ->money('RUB')
                    ->formatStateUsing(fn ($state) => (float)$state / 100),
                Tables\Columns\TextColumn::make('payment_status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'deposit_paid' => 'warning',
                        'not_paid' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('reserved_at')
                    ->label('Date')
                    ->dateTime('d M Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'reserved' => 'Reserved',
                        'paid_full' => 'Paid Full',
                        'pending' => 'Pending',
                    ]),
                Tables\Filters\SelectFilter::make('wedding_event_id')
                    ->relationship('weddingEvent', 'title')
                    ->label('Event Filter'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('mark_as_paid')
                    ->label('Pay Full')
                    ->icon('heroicon-m-credit-card')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (WeddingBooking $record) {
                        $record->update([
                            'status' => 'paid_full',
                            'payment_status' => 'fully_paid',
                            'paid_at' => now(),
                        ]);
                        Notification::make()->title('Booking paid in full!')->success()->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
        }

        public static function getEloquentQuery(): Builder
        {
            return parent::getEloquentQuery()
                ->where('wedding_bookings.tenant_id', filament()->getTenant()->id);
        }
}
