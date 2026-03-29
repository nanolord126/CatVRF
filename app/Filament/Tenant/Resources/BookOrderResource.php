<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use App\Domains\BooksAndLiterature\Books\Models\BookOrder;
use App\Domains\BooksAndLiterature\Books\Models\Book;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * BookOrderResource (Layer 5/9)
 * Comprehensive Filament resource for tracking B2C/B2B Book Orders.
 * Features: Order items, payment audit, B2B company tracking, and status transitions.
 */
class BookOrderResource extends Resource
{
    protected static ?string $model = BookOrder::class;
    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    protected static ?string $navigationGroup = 'Books & Education';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Customer & Order Strategy')
                    ->columns(3)
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->relationship('user', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->hint('Order owner (User).'),
                        Forms\Components\Select::make('store_id')
                            ->relationship('store', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->hint('Origin Bookstore for fulfillment.'),
                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => 'Pending (New Order)',
                                'processing' => 'Processing (Picking Items)',
                                'delivered' => 'Delivered (Complete)',
                                'returned' => 'Returned',
                                'cancelled' => 'Cancelled',
                            ])
                            ->required()
                            ->default('pending')
                            ->columnSpan(1),
                    ]),

                Forms\Components\Section::make('B2B Institutional Details')
                    ->collapsible()
                    ->collapsed(fn (?BookOrder $record) => is_null($record?->b2b_company_id))
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('b2b_company_id')
                            ->label('B2B Company / Institution')
                            ->relationship('b2bCompany', 'name')
                            ->searchable()
                            ->preload()
                            ->hint('If selected, order uses wholesale pricing (B2B Price).'),
                        Forms\Components\TextInput::make('metadata.invoice_number')
                            ->label('Pro Forma Invoice #')
                            ->maxLength(100),
                        Forms\Components\TextInput::make('metadata.tax_id')
                            ->label('Institutional INN / TaxID')
                            ->maxLength(20),
                        Forms\Components\Toggle::make('metadata.tax_deductible')
                            ->label('Eligible for Educational Tax Deduction')
                            ->default(false),
                    ]),

                Forms\Components\Section::make('Financial Totals & Audit')
                    ->columns(3)
                    ->schema([
                        Forms\Components\TextInput::make('total_amount')
                            ->numeric()
                            ->required()
                            ->suffix('RUB × 100 (Kopecks)')
                            ->state(fn (?BookOrder $record) => $record?->total_amount),
                        Forms\Components\TextInput::make('metadata.applied_discount')
                            ->numeric()
                            ->label('Applied Volume Discount')
                            ->suffix('Kopecks'),
                        Forms\Components\Select::make('payment_status')
                            ->options([
                                'unpaid' => 'Awaiting Payment',
                                'paid' => 'Paid In Full',
                                'refunded' => 'Payment Refunded',
                                'failed' => 'Transaction Failed',
                            ])
                            ->required()
                            ->default('unpaid'),
                        Forms\Components\TextInput::make('correlation_id')
                            ->disabled()
                            ->label('Payment Trace CID')
                            ->hint('Correlation ID from Payment Gateway.'),
                        Forms\Components\TextInput::make('uuid')
                            ->disabled()
                            ->label('System UUID'),
                    ]),

                Forms\Components\Section::make('Order Line Items')
                    ->schema([
                        Forms\Components\Repeater::make('metadata.items')
                            ->label('Books in Order')
                            ->schema([
                                Forms\Components\Select::make('book_id')
                                    ->label('Book')
                                    ->required()
                                    ->options(fn () => Book::pluck('title', 'id'))
                                    ->searchable(),
                                Forms\Components\TextInput::make('quantity')
                                    ->numeric()
                                    ->required()
                                    ->default(1),
                                Forms\Components\TextInput::make('unit_price')
                                    ->numeric()
                                    ->required()
                                    ->label('Price at Checkout (Kopecks)')
                                    ->hint('Preserves price if book price changes later.'),
                            ])
                            ->columns(3)
                            ->itemLabel(fn (array $state): ?string => Book::find($state['book_id'] ?? null)?->title ?? 'New Line Item'),
                    ]),
            ]);

    public static function getPages(): array
    {
        return [
            'index' => Pages\\ListBookOrder::route('/'),
            'create' => Pages\\CreateBookOrder::route('/create'),
            'edit' => Pages\\EditBookOrder::route('/{record}/edit'),
            'view' => Pages\\ViewBookOrder::route('/{record}'),
        ];

    public static function getPages(): array
    {
        return [
            'index' => Pages\\ListBookOrder::route('/'),
            'create' => Pages\\CreateBookOrder::route('/create'),
            'edit' => Pages\\EditBookOrder::route('/{record}/edit'),
            'view' => Pages\\ViewBookOrder::route('/{record}'),
        ];

    public static function getPages(): array
    {
        return [
            'index' => Pages\\ListBookOrder::route('/'),
            'create' => Pages\\CreateBookOrder::route('/create'),
            'edit' => Pages\\EditBookOrder::route('/{record}/edit'),
            'view' => Pages\\ViewBookOrder::route('/{record}'),
        ];
    }
}
