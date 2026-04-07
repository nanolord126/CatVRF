<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Entertainment;

use Filament\Resources\Resource;

final class TicketResource extends Resource
{

    protected static ?string $model = Ticket::class;

        protected static ?string $navigationIcon = 'heroicon-o-qr-code';

        protected static ?string $navigationGroup = 'Entertainment';

        protected static ?string $tenantOwnershipRelationshipName = 'tenant';

        public static function form(Form $form): Form
        {
            return $form
                ->schema([
                    Forms\Components\Section::make('Ticket Identification')
                        ->description('Secure ticket data and status')
                        ->schema([
                            Forms\Components\Select::make('booking_id')
                                ->relationship('booking', 'uuid', fn (Builder $query) => $query->where('tenant_id', filament()->getTenant()->id))
                                ->required()
                                ->searchable()
                                ->preload(),
                            Forms\Components\TextInput::make('ticket_number')
                                ->required()
                                ->maxLength(50)
                                ->default(fn () => 'TK-' . strtoupper(Str::random(10))),
                            Forms\Components\Select::make('status')
                                ->options([
                                    'active' => 'Active',
                                    'used' => 'Used',
                                    'void' => 'Void (Cancelled)',
                                    'expired' => 'Expired',
                                ])
                                ->required()
                                ->default('active'),
                            Forms\Components\DateTimePicker::make('scanned_at')
                                ->label('Scan Timestamp')
                                ->disabled()
                                ->native(false),
                        ])->columns(2),

                    Forms\Components\Section::make('Seat & Metadata')
                        ->schema([
                            Forms\Components\KeyValue::make('seat_info')
                                ->label('Specific Seat Data (Row/Col)')
                                ->required(),
                            Forms\Components\TextInput::make('uuid')
                                ->disabled()
                                ->dehydrated()
                                ->default(fn () => (string) Str::uuid()),
                            Forms\Components\TextInput::make('correlation_id')
                                ->disabled()
                                ->dehydrated()
                                ->default(fn () => (string) Str::uuid()),
                        ])->columns(2),
                ]);
        }

        public static function table(Table $table): Table
        {
            return $table
                ->columns([
                    Tables\Columns\TextColumn::make('ticket_number')
                        ->label('Number')
                        ->searchable()
                        ->sortable()
                        ->copyable(),
                    Tables\Columns\TextColumn::make('booking.uuid')
                        ->label('Booking ID')
                        ->searchable()
                        ->sortable(),
                    Tables\Columns\BadgeColumn::make('status')
                        ->colors([
                            'success' => 'active',
                            'info' => 'used',
                            'danger' => 'void',
                            'secondary' => 'expired',
                        ]),
                    Tables\Columns\TextColumn::make('scanned_at')
                        ->label('Scanned At')
                        ->dateTime()
                        ->sortable(),
                    Tables\Columns\TextColumn::make('created_at')
                        ->dateTime()
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: true),
                ])
                ->filters([
                    Tables\Filters\SelectFilter::make('status'),
                ])
                ->actions([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\ViewAction::make(),
                ])
                ->bulkActions([
                    Tables\Actions\BulkActionGroup::make([
                        Tables\Actions\DeleteBulkAction::make(),
                    ]),
                ]);
        }

        public static function getPages(): array
        {
            return [
                'index' => Pages\ListTickets::route('/'),
                'create' => Pages\CreateTicket::route('/create'),
                'edit' => Pages\EditTicket::route('/{record}/edit'),
            ];
        }

        public static function getEloquentQuery(): Builder
        {
            return parent::getEloquentQuery()
                ->where('tenant_id', filament()->getTenant()->id);
        }
}
