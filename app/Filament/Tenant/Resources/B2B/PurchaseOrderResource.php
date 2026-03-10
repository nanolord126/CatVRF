<?php

namespace App\Filament\Tenant\Resources\B2B;

use App\Filament\Tenant\Resources\B2B\PurchaseOrderResource\Pages;
use App\Models\B2B\PurchaseOrder;
use App\Models\B2B\Supplier;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PurchaseOrderResource extends Resource
{
    protected static ?string $model = PurchaseOrder::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    protected static ?string $navigationGroup = 'B2B Supply Chain';
    protected static ?string $label = 'Закупка';
    protected static ?string $pluralLabel = 'Закупки';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Purchase Order Overview')
                    ->schema([
                        Forms\Components\TextInput::make('order_number')
                            ->disabled()
                            ->dehydrated(false)
                            ->label('Номер заказа'),
                        Forms\Components\Select::make('supplier_id')->relationship('supplier', 'name')->required(),
                        Forms\Components\Select::make('status')
                            ->options([
                                'DRAFT' => 'Черновик',
                                'PENDING' => 'Отправлен',
                                'APPROVED' => 'Утвержден',
                                'SHIPPED' => 'В пути',
                                'DELIVERED' => 'Принят',
                                'CANCELLED' => 'Отменен',
                            ])->default('DRAFT'),
                        Forms\Components\TextInput::make('total_amount')->numeric()->prefix('$')->disabled(),
                    ])->columns(2),

                Forms\Components\Section::make('Items')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->relationship()
                            ->schema([
                                Forms\Components\Select::make('product_id')
                                    ->relationship('product', 'name')
                                    ->required()
                                    ->label('Товар'),
                                Forms\Components\TextInput::make('quantity')->numeric()->required()->label('Кол-во'),
                                Forms\Components\TextInput::make('unit_price')->numeric()->required()->label('Цена ед.'),
                            ])->columns(3)
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order_number')->searchable(),
                Tables\Columns\TextColumn::make('supplier.name')->label('Поставщик'),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'primary' => 'DRAFT',
                        'warning' => 'PENDING',
                        'success' => 'DELIVERED',
                        'danger' => 'CANCELLED',
                    ]),
                Tables\Columns\TextColumn::make('total_amount')->money('USD')->sortable(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->label('Создан'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    'DRAFT' => 'Draft',
                    'PENDING' => 'Pending',
                    'DELIVERED' => 'Delivered',
                ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('payWithWallet')
                    ->label('Оплатить B2B')
                    ->icon('heroicon-o-credit-card')
                    ->color('success')
                    ->requiresConfirmation()
                    ->hidden(fn (PurchaseOrder $record) => $record->payment_status === 'PAID')
                    ->action(fn (PurchaseOrder $record) => (new \App\Services\B2B\B2BWalletPaymentService())->payPurchaseOrder($record)),
            ])
            ->headerActions([
                // Спец. кнопка запуска AI-снабженца
                Tables\Actions\Action::make('runAIProcurement')
                    ->label('Запуск AI-Снабжения')
                    ->action(fn () => (new \App\Services\B2B\ProcurementAIPrognostService())->analyzeAndProposeOrders())
                    ->color('info')
                    ->icon('heroicon-o-cpu-chip'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPurchaseOrders::route('/'),
            'create' => Pages\CreatePurchaseOrder::route('/create'),
            'edit' => Pages\EditPurchaseOrder::route('/{record}/edit'),
        ];
    }
}
