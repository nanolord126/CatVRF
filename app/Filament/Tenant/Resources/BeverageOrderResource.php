<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class BeverageOrderResource extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected static ?string $model = BeverageOrder::class;

        protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';
        protected static ?string $navigationGroup = 'Beverage Management';
        protected static ?int $navigationSort = 3;

        public static function form(Form $form): Form
        {
            return $form
                ->schema([
                    Forms\Components\Section::make('Order Summary')
                        ->description('Primary order identification and status details.')
                        ->columns(3)
                        ->schema([
                            Forms\Components\TextInput::make('uuid')
                                ->disabled()
                                ->dehydrated(false)
                                ->label('Order UUID'),
                            Forms\Components\Select::make('status')
                                ->required()
                                ->options([
                                    'pending' => 'Pending Confirmation',
                                    'confirmed' => 'Verified & Paid',
                                    'preparing' => 'In Preparation',
                                    'ready' => 'Ready for Pickup/Delivery',
                                    'completed' => 'Finalized',
                                    'cancelled' => 'Refined/Cancelled',
                                ])
                                ->native(false),
                            Forms\Components\Select::make('payment_status')
                                ->required()
                                ->options([
                                    'pending' => 'Pending Payment',
                                    'authorized' => 'Payment Authorized',
                                    'captured' => 'Payment Captured',
                                    'refunded' => 'Order Refunded',
                                    'failed' => 'Transaction Failed',
                                ])
                                ->native(false),
                        ]),

                    Forms\Components\Section::make('Client & Logistics')
                        ->description('Who is ordering and how to reach them.')
                        ->columns(2)
                        ->schema([
                            Forms\Components\Select::make('shop_id')
                                ->relationship('shop', 'name')
                                ->required()
                                ->searchable()
                                ->preload(),
                            Forms\Components\Select::make('user_id')
                                ->relationship('user', 'name')
                                ->required()
                                ->searchable()
                                ->preload(),
                            Forms\Components\TextInput::make('delivery_address')
                                ->placeholder('Leave empty for dine-in/pickup')
                                ->maxLength(500),
                            Forms\Components\TextInput::make('contact_phone')
                                ->tel()
                                ->required(),
                        ]),

                    Forms\Components\Section::make('Transaction Financials')
                        ->description('Cost breakdown including commissions and fraud metrics.')
                        ->columns(3)
                        ->schema([
                            Forms\Components\TextInput::make('total_price')
                                ->numeric()
                                ->prefix('RUB')
                                ->required()
                                ->label('Final Price (Kopeks)'),
                            Forms\Components\TextInput::make('commission_amount')
                                ->numeric()
                                ->prefix('RUB')
                                ->required()
                                ->label('Platform Commission'),
                            Forms\Components\TextInput::make('ml_fraud_score')
                                ->numeric()
                                ->disabled()
                                ->label('AI Fraud Risk Score (0-1)'),
                        ]),

                    Forms\Components\Section::make('Detailed Item Composition')
                        ->description('List of beverages and custom modifiers.')
                        ->columns(1)
                        ->schema([
                            Forms\Components\Repeater::make('items_json')
                                ->label('Drink Composition')
                                ->schema([
                                    Forms\Components\Select::make('beverage_item_id')
                                        ->relationship('items', 'name')
                                        ->required()
                                        ->searchable(),
                                    Forms\Components\TextInput::make('quantity')
                                        ->numeric()
                                        ->default(1)
                                        ->required()
                                        ->minValue(1),
                                    Forms\Components\TextInput::make('price_at_order')
                                        ->numeric()
                                        ->prefix('RUB')
                                        ->required(),
                                    Forms\Components\TagsInput::make('modifiers')
                                        ->placeholder('e.g., No sugar, Double shot, Soy milk'),
                                ])
                                ->columns(4)
                                ->itemLabel(fn (array $state): ?string => $state['beverage_item_id'] ?? null)
                                ->collapsible(),
                        ]),

                    Forms\Components\Section::make('Audit & Identification')
                        ->description('System identifiers for traceability.')
                        ->columns(2)
                        ->collapsed()
                        ->schema([
                            Forms\Components\TextInput::make('idempotency_key')
                                ->disabled()
                                ->label('Idempotency Token'),
                            Forms\Components\TextInput::make('correlation_id')
                                ->disabled()
                                ->label('Audit Correlation ID'),
                            Forms\Components\KeyValue::make('tags')
                                ->label('Analytical Tags'),
                        ]),
                ]);

        public static function getPages(): array
        {
            return [
                'index' => Pages\\ListBeverageOrder::route('/'),
                'create' => Pages\\CreateBeverageOrder::route('/create'),
                'edit' => Pages\\EditBeverageOrder::route('/{record}/edit'),
                'view' => Pages\\ViewBeverageOrder::route('/{record}'),
            ];

        public static function getPages(): array
        {
            return [
                'index' => Pages\\ListBeverageOrder::route('/'),
                'create' => Pages\\CreateBeverageOrder::route('/create'),
                'edit' => Pages\\EditBeverageOrder::route('/{record}/edit'),
                'view' => Pages\\ViewBeverageOrder::route('/{record}'),
            ];

        public static function getPages(): array
        {
            return [
                'index' => Pages\\ListBeverageOrder::route('/'),
                'create' => Pages\\CreateBeverageOrder::route('/create'),
                'edit' => Pages\\EditBeverageOrder::route('/{record}/edit'),
                'view' => Pages\\ViewBeverageOrder::route('/{record}'),
            ];
        }
}
