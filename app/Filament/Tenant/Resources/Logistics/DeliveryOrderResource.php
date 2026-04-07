<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Logistics;

use Filament\Resources\Resource;

final class DeliveryOrderResource extends Resource
{

    protected static ?string $model = DeliveryOrder::class;

        protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';
        protected static ?string $navigationGroup = 'Logistics & Fleet';
        protected static ?int $navigationSort = 2;

        public static function form(Form $form): Form
        {
            return $form
                ->schema([
                    Forms\Components\Section::make('Delivery Status & Courier')
                        ->schema([
                            Forms\Components\Select::make('status')
                                ->options([
                                    'pending' => 'Pending',
                                    'assigned' => 'Assigned',
                                    'picked_up' => 'Picked Up',
                                    'delivered' => 'Delivered',
                                    'cancelled' => 'Cancelled',
                                ])
                                ->required()
                                ->default('pending'),
                            Forms\Components\Select::make('courier_id')
                                ->relationship('courier', 'full_name')
                                ->searchable()
                                ->nullable(),
                            Forms\Components\TextInput::make('total_price_kopecks')
                                ->label('Total Price (kop.)')
                                ->numeric()
                                ->required()
                                ->suffix('коп.'),
                            Forms\Components\TextInput::make('surge_multiplier')
                                ->numeric()
                                ->default(1.0)
                                ->readOnly(),
                        ])->columns(2),

                    Forms\Components\Grid::make(2)
                        ->schema([
                            Forms\Components\Section::make('Pickup Location')
                                ->schema([
                                    Forms\Components\TextInput::make('pickup_address')->required(),
                                    Forms\Components\TextInput::make('pickup_lat')->numeric()->required(),
                                    Forms\Components\TextInput::make('pickup_lon')->numeric()->required(),
                                ])->columnSpan(1),
                            Forms\Components\Section::make('Dropoff Location')
                                ->schema([
                                    Forms\Components\TextInput::make('dropoff_address')->required(),
                                    Forms\Components\TextInput::make('dropoff_lat')->numeric()->required(),
                                    Forms\Components\TextInput::make('dropoff_lon')->numeric()->required(),
                                ])->columnSpan(1),
                        ]),

                    Forms\Components\Section::make('System & Audit')
                        ->schema([
                            Forms\Components\TextInput::make('uuid')
                                ->default(\Illuminate\Support\Str::uuid()->toString())
                                ->readOnly(),
                            Forms\Components\TextInput::make('correlation_id')
                                ->readOnly(),
                            Forms\Components\KeyValue::make('tags')
                                ->nullable(),
                        ])->collapsed(),
                ]);
        }

        public static function table(Table $table): Table
        {
            return $table
                ->columns([
                    Tables\Columns\TextColumn::make('uuid')
                        ->label('ID')
                        ->limit(8)
                        ->searchable(),
                    Tables\Columns\BadgeColumn::make('status')
                        ->colors([
                            'warning' => 'pending',
                            'primary' => 'assigned',
                            'info' => 'picked_up',
                            'success' => 'delivered',
                            'danger' => 'cancelled',
                        ]),
                    Tables\Columns\TextColumn::make('courier.full_name')
                        ->label('Courier')
                        ->placeholder('Not assigned'),
                    Tables\Columns\TextColumn::make('total_price_kopecks')
                        ->label('Price')
                        ->formatStateUsing(fn ($state) => number_format($state / 100, 2) . ' ₽'),
                    Tables\Columns\TextColumn::make('surge_multiplier')
                        ->label('Surge')
                        ->numeric(2),
                    Tables\Columns\TextColumn::make('created_at')
                        ->dateTime()
                        ->sortable(),
                ])
                ->defaultSort('created_at', 'desc')
                ->filters([
                    Tables\Filters\SelectFilter::make('status'),
                ])
                ->actions([
                    Tables\Actions\EditAction::make(),
                ]);
        }

        public static function getEloquentQuery(): Builder
        {
            return parent::getEloquentQuery()
                ->with(['courier.user', 'courier.vehicle'])
                ->orderBy('id', 'desc');
        }

        public static function getPages(): array
        {
            return [
                'index' => Pages\ListDeliveryOrders::route('/'),
                'create' => Pages\CreateDeliveryOrder::route('/create'),
                'edit' => Pages\EditDeliveryOrder::route('/{record}/edit'),
            ];
        }
}
