<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use App\Domains\Food\Beverages\Models\BeverageOrder;
use App\Filament\Tenant\Resources\BeverageOrderResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

final class BeverageOrderResource extends Resource
{
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
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('uuid')
                    ->label('ID')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Customer')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('shop.name')
                    ->label('Venue')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('total_price')
                    ->money('RUB', divideBy: 100)
                    ->label('Total')
                    ->sortable()
                    ->color('success')
                    ->weight('bold'),
                Tables\Columns\SelectColumn::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'confirmed' => 'Verified',
                        'preparing' => 'Kitchen',
                        'ready' => 'Ready',
                        'completed' => 'Finalized',
                        'cancelled' => 'Dropped',
                    ])
                    ->label('Fulfillment'),
                Tables\Columns\BadgeColumn::make('payment_status')
                    ->colors([
                        'warning' => 'pending',
                        'info' => 'authorized',
                        'success' => 'captured',
                        'danger' => ['failed', 'refunded'],
                    ])
                    ->label('Fin Status'),
                Tables\Columns\TextColumn::make('ml_fraud_score')
                    ->label('Risk')
                    ->formatStateUsing(fn ($state) => number_format((float)$state, 2))
                    ->color(fn ($state) => (float)$state > 0.7 ? 'danger' : ((float)$state > 0.3 ? 'warning' : 'success'))
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d.m.Y H:i')
                    ->label('Ordered At')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending Confirmation',
                        'preparing' => 'Currently Preparing',
                        'ready' => 'Ready for Handover',
                    ]),
                Tables\Filters\SelectFilter::make('shop_id')
                    ->relationship('shop', 'name')
                    ->label('Filter by Venue'),
                Tables\Filters\TernaryFilter::make('is_suspicious')
                    ->label('High Fraud Risk')
                    ->queries(
                        true: fn (Builder $query) => $query->where('ml_fraud_score', '>', 0.7),
                        false: fn (Builder $query) => $query->where('ml_fraud_score', '<=', 0.7),
                    ),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('mark_ready')
                    ->label('Set Ready')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(fn (BeverageOrder $record) => $record->update(['status' => 'ready']))
                    ->visible(fn (BeverageOrder $record) => $record->status === 'preparing'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBeverageOrders::route('/'),
            'create' => Pages\CreateBeverageOrder::route('/create'),
            'edit' => Pages\EditBeverageOrder::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['shop', 'user', 'items'])
            ->latest();
    }
}
