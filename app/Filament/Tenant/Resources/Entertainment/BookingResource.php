<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Entertainment;

use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;

final class BookingResource extends Resource
{

    protected static ?string $model = Booking::class;

        protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

        protected static ?string $navigationGroup = 'Entertainment';

        protected static ?string $tenantOwnershipRelationshipName = 'tenant';

        public static function form(Form $form): Form
        {
            return $form
                ->schema([
                    Forms\Components\Section::make('General Information')
                        ->description('Details about the customer booking')
                        ->schema([
                            Forms\Components\Select::make('event_id')
                                ->relationship('event', 'name', fn (Builder $query) => $query->where('tenant_id', filament()->getTenant()->id))
                                ->required()
                                ->searchable()
                                ->preload(),
                            Forms\Components\TextInput::make('user_id')
                                ->numeric()
                                ->required()
                                ->label('Client User ID'),
                            Forms\Components\Select::make('status')
                                ->options([
                                    'pending' => 'Pending',
                                    'confirmed' => 'Confirmed',
                                    'paid' => 'Paid',
                                    'cancelled' => 'Cancelled',
                                    'expired' => 'Expired',
                                ])
                                ->required()
                                ->default('pending'),
                        ])->columns(2),

                    Forms\Components\Section::make('Seat Configuration')
                        ->schema([
                            Forms\Components\JsonEditor::make('seats')
                                ->label('Booked Seats Layout (JSON)')
                                ->required(),
                            Forms\Components\TextInput::make('total_amount_kopecks')
                                ->label('Total (Kopecks)')
                                ->numeric()
                                ->required()
                                ->prefix('RUB'),
                        ]),

                    Forms\Components\Section::make('Metadata')
                        ->schema([
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
                    Tables\Columns\TextColumn::make('uuid')
                        ->label('Booking ID')
                        ->searchable()
                        ->copyable()
                        ->limit(8),
                    Tables\Columns\TextColumn::make('event.name')
                        ->sortable(),
                    Tables\Columns\TextColumn::make('user_id')
                        ->label('Client ID')
                        ->sortable(),
                    Tables\Columns\BadgeColumn::make('status')
                        ->colors([
                            'warning' => 'pending',
                            'info' => 'confirmed',
                            'success' => 'paid',
                            'danger' => 'cancelled',
                            'secondary' => 'expired',
                        ]),
                    Tables\Columns\TextColumn::make('total_amount_kopecks')
                        ->label('Total (RUB)')
                        ->money('rub', divideBy: 100)
                        ->sortable(),
                    Tables\Columns\TextColumn::make('created_at')
                        ->dateTime()
                        ->sortable(),
                ])
                ->filters([
                    Tables\Filters\SelectFilter::make('status'),
                    Tables\Filters\SelectFilter::make('event_id')
                        ->relationship('event', 'name', fn (Builder $query) => $query->where('tenant_id', filament()->getTenant()->id)),
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
                'index' => Pages\ListBookings::route('/'),
                'create' => Pages\CreateBooking::route('/create'),
                'edit' => Pages\EditBooking::route('/{record}/edit'),
            ];
        }

        public static function getEloquentQuery(): Builder
        {
            return parent::getEloquentQuery()
                ->where('tenant_id', filament()->getTenant()->id);
        }
}
