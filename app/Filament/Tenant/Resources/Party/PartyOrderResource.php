<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Party;

use App\Models\Party\PartyOrder;
use App\Models\Party\PartyStore;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Illuminate\Database\Eloquent\Builder;

/**
 * PartyOrderResource.
 * Management for celebratory orders with prepayment tracking.
 */
final class PartyOrderResource extends Resource
{
    protected static ?string $model = PartyOrder::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    protected static ?string $navigationGroup = 'Party Supplies';
    protected static ?string $modelLabel = 'Event Order';
    protected static ?string $pluralModelLabel = 'Event Orders';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Order Summary')
                    ->description('General order and event information.')
                    ->schema([
                        Select::make('party_store_id')
                            ->label('Party Store')
                            ->relationship('store', 'name')
                            ->required(),
                        Select::make('user_id')
                            ->label('Client')
                            ->relationship('user', 'name')
                            ->searchable(),
                        DateTimePicker::make('event_date')
                            ->label('Celebration Date')
                            ->required(),
                        Select::make('status')
                            ->options([
                                'pending' => 'Pending Confirmation',
                                'confirmed' => 'Confirmed Decorating',
                                'in_preparation' => 'Preparation (Balloons/Fireworks)',
                                'ready' => 'Ready for Pickup/Delivery',
                                'delivered' => 'Event Handled',
                                'cancelled' => 'Cancelled',
                            ])->required()->default('pending'),
                        Toggle::make('is_b2b')
                            ->label('Corporate/Wholesale Order'),
                    ])->columns(2),

                Section::make('Financials')
                    ->description('Tracking total, prepayment and payment status.')
                    ->schema([
                        TextInput::make('total_cents')
                            ->required()
                            ->numeric()
                            ->label('Order Total (Cents)')
                            ->default(0),
                        TextInput::make('prepayment_cents')
                            ->required()
                            ->numeric()
                            ->label('Prepayment Required (Cents)')
                            ->default(0),
                        Select::make('payment_status')
                            ->options([
                                'unpaid' => 'Unpaid',
                                'partially_paid' => 'Prepayment Received',
                                'paid' => 'Fully Paid',
                                'refunded' => 'Refunded',
                            ])->required()->default('unpaid'),
                    ])->columns(2),

                Section::make('Items & Ship-To')
                    ->description('Detailed items snapshot and contact details.')
                    ->schema([
                        KeyValue::make('items_json')
                            ->label('Items History (Snapshot)')
                            ->keyLabel('Item/SKU')
                            ->valueLabel('Quantity/Price'),
                        KeyValue::make('contact_info')
                            ->label('Delivery/Contact Info')
                            ->keyLabel('Field')
                            ->valueLabel('Detail'),
                    ])->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('uuid')
                    ->searchable()
                    ->label('Order #')
                    ->limit(10),
                TextColumn::make('store.name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('total_cents')
                    ->money('RUB')
                    ->sortable()
                    ->label('Total'),
                TextColumn::make('prepayment_cents')
                    ->money('RUB')
                    ->label('Prepay'),
                BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending',
                        'primary' => 'confirmed',
                        'success' => 'delivered',
                        'danger' => 'cancelled',
                    ]),
                TextColumn::make('event_date')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // (Optional) Add event date range filter here
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (function_exists('tenant') && tenant()) {
            $query->where('tenant_id', tenant()->id);
        }

        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Tenant\Resources\Party\PartyOrderResource\Pages\ListPartyOrders::route('/'),
            'create' => \App\Filament\Tenant\Resources\Party\PartyOrderResource\Pages\CreatePartyOrder::route('/create'),
            'edit' => \App\Filament\Tenant\Resources\Party\PartyOrderResource\Pages\EditPartyOrder::route('/{record}/edit'),
        ];
    }
}
