<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Cleaning;

use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;

final class CleaningOrderResource extends Resource
{

    protected static ?string $model = CleaningOrder::class;

        protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
        protected static ?string $navigationGroup = 'Cleaning Services';
        protected static ?int $navigationSort = 3;

        public static function form(Form $form): Form
        {
            return $form
                ->schema([
                    Group::make()
                        ->schema([
                            Section::make('Order Core Details')
                                ->description('Service assignment and timing.')
                                ->schema([
                                    Select::make('cleaning_service_id')
                                        ->relationship('service', 'name')
                                        ->label('Type of Cleaning')
                                        ->required()
                                        ->reactive()
                                        ->afterStateUpdated(function ($state, callable $set) {
                                            $service = ServiceModel::find($state);
                                            if ($service) {
                                                $set('base_price', $service->price);
                                                $set('final_price', $service->price);
                                            }
                                        })
                                        ->searchable(),

                                    Select::make('user_id')
                                        ->relationship('user', 'name')
                                        ->label('Client Account')
                                        ->required()
                                        ->searchable(),

                                    Select::make('status')
                                        ->options([
                                            'pending' => 'Pending Confirmation',
                                            'confirmed' => 'Confirmed (Deposit Paid)',
                                            'starting' => 'Starting (Cleaner Arrived)',
                                            'processing' => 'Cleaning in Process',
                                            'checking' => 'Client Quality Check',
                                            'completed' => 'Fully Completed',
                                            'cancelled' => 'Cancelled',
                                        ])
                                        ->required()
                                        ->default('pending'),

                                    DateTimePicker::make('scheduled_at')
                                        ->label('Execution Time')
                                        ->required()
                                        ->native(false),
                                ])
                                ->columns(2),

                            Section::make('Financial & B2B/B2C Logic')
                                ->description('Price multipliers and tax logic.')
                                ->schema([
                                    TextInput::make('base_price')
                                        ->numeric()
                                        ->label('Base Service Price (kopecks)')
                                        ->required()
                                        ->disabled()
                                        ->suffix('kop.'),

                                    TextInput::make('final_price')
                                        ->numeric()
                                        ->label('Final Adjusted Price (kopecks)')
                                        ->required()
                                        ->suffix('kop.')
                                        ->reactive(),

                                    Toggle::make('is_commercial')
                                        ->label('Commercial Property (B2B)')
                                        ->onIcon('heroicon-m-building-office')
                                        ->offIcon('heroicon-m-home')
                                        ->reactive()
                                        ->afterStateUpdated(function ($state, callable $set, $get) {
                                            $price = $get('base_price');
                                            if ($state) {
                                                $set('final_price', (int)($price * 1.4));
                                            } else {
                                                $set('final_price', $price);
                                            }
                                        }),

                                    Select::make('payment_status')
                                        ->options([
                                            'pending' => 'Waiting for Deposit',
                                            'deposit_paid' => 'Deposit (30%) Received',
                                            'fully_paid' => 'Fully Paid (100%)',
                                            'refunded' => 'Refunded Action',
                                        ])
                                        ->default('pending')
                                        ->required(),
                                ])
                                ->columns(2),
                        ])
                        ->columnSpan(['lg' => 2]),

                    Group::make()
                        ->schema([
                            Section::make('Security & Traceability')
                                ->schema([
                                    TextInput::make('uuid')
                                        ->disabled()
                                        ->label('Order UUID'),

                                    TextInput::make('correlation_id')
                                        ->disabled()
                                        ->label('Trace ID'),

                                    TextInput::make('idempotency_key')
                                        ->disabled()
                                        ->label('Idempotency Token'),

                                    Textarea::make('meta.special_instructions')
                                        ->label('Cleaner Notes')
                                        ->rows(3),
                                ]),
                        ])
                        ->columnSpan(['lg' => 1]),
                ])
                ->columns(3);
        }

        public static function table(Table $table): Table
        {
            return $table
                ->columns([
                    TextColumn::make('uuid')
                        ->label('Order ID')
                        ->searchable()
                        ->copyable()
                        ->limit(8),

                    TextColumn::make('user.name')
                        ->label('Client')
                        ->searchable()
                        ->sortable(),

                    TextColumn::make('service.name')
                        ->label('Cleaning Type')
                        ->sortable(),

                    BadgeColumn::make('status')
                        ->colors([
                            'danger' => 'cancelled',
                            'warning' => 'pending',
                            'primary' => 'starting',
                            'success' => 'completed',
                            'info' => 'processing',
                        ])
                        ->sortable(),

                    TextColumn::make('final_price')
                        ->label('Price (Rub)')
                        ->formatStateUsing(fn ($state) => number_format($state / 100, 2, '.', ' ') . ' ₽')
                        ->sortable(),

                    TextColumn::make('scheduled_at')
                        ->label('Execution Time')
                        ->dateTime()
                        ->sortable(),
                ])
                ->filters([
                    SelectFilter::make('status')
                        ->options([
                            'pending' => 'Pending',
                            'starting' => 'Starting',
                            'completed' => 'Completed',
                            'cancelled' => 'Cancelled',
                        ]),
                    \Filament\Tables\Filters\TernaryFilter::make('is_commercial')
                        ->label('B2B Order'),
                ])
                ->actions([
                    \Filament\Tables\Actions\EditAction::make(),
                    \Filament\Tables\Actions\ViewAction::make(),
                ])
                ->bulkActions([
                    \Filament\Tables\Actions\DeleteBulkAction::make(),
                ]);
        }

        public static function getEloquentQuery(): Builder
        {
            return parent::getEloquentQuery()
                ->with(['user', 'service'])
                ->latest('id');
        }

        public static function getPages(): array
        {
            return [
                'index' => \App\Filament\Tenant\Resources\Cleaning\Pages\ListCleaningOrders::route('/'),
                'create' => \App\Filament\Tenant\Resources\Cleaning\Pages\CreateCleaningOrder::route('/create'),
                'edit' => \App\Filament\Tenant\Resources\Cleaning\Pages\EditCleaningOrder::route('/{record}/edit'),
            ];
        }
}
