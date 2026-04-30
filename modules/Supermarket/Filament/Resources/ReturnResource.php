<?php

declare(strict_types=1);

namespace Modules\Supermarket\Filament\Resources;

use Modules\Supermarket\Infrastructure\Models\Return;
use Modules\Supermarket\Infrastructure\Models\ReturnItem;
use Modules\Supermarket\Domain\Enums\ReturnStatus;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;

class ReturnResource extends Resource
{
    protected static ?string $model = Return::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-uturn-left';
    
    protected static ?string $navigationGroup = 'Supermarket';
    
    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Return Information')
                    ->schema([
                        Forms\Components\Select::make('order_id')
                            ->relationship('order', 'id')
                            ->searchable()
                            ->required()
                            ->preload(),
                        
                        Forms\Components\Select::make('buyer_id')
                            ->relationship('buyer', 'name')
                            ->searchable()
                            ->required()
                            ->preload(),
                        
                        Forms\Components\Select::make('seller_id')
                            ->relationship('seller', 'shop_name')
                            ->searchable()
                            ->required()
                            ->preload(),
                        
                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'approved' => 'Approved',
                                'rejected' => 'Rejected',
                                'completed' => 'Completed',
                                'refunded' => 'Refunded',
                            ])
                            ->required(),
                        
                        Forms\Components\Select::make('reason_type')
                            ->options([
                                'spoiled' => 'Spoiled',
                                'wrong_item' => 'Wrong Item',
                                'changed_mind' => 'Changed Mind',
                                'damaged' => 'Damaged',
                                'other' => 'Other',
                            ])
                            ->required(),
                        
                        Forms\Components\Textarea::make('reason_comment')
                            ->rows(3),
                        
                        Forms\Components\TextInput::make('total_amount')
                            ->numeric()
                            ->prefix('₽')
                            ->required(),
                        
                        Forms\Components\TextInput::make('refund_amount')
                            ->numeric()
                            ->prefix('₽')
                            ->required(),
                        
                        Forms\Components\Toggle::make('is_cold_chain')
                            ->label('Cold Chain'),
                        
                        Forms\Components\Select::make('return_method')
                            ->options([
                                'pickup' => 'Pickup',
                                'courier' => 'Courier',
                                'self_delivery' => 'Self Delivery',
                            ])
                            ->required(),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Items')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->relationship()
                            ->schema([
                                Forms\Components\Select::make('product_id')
                                    ->relationship('product', 'name')
                                    ->searchable()
                                    ->required()
                                    ->preload(),
                                
                                Forms\Components\TextInput::make('quantity')
                                    ->numeric()
                                    ->required()
                                    ->minValue(1),
                                
                                Forms\Components\TextInput::make('price_per_unit')
                                    ->numeric()
                                    ->prefix('₽')
                                    ->required(),
                                
                                Forms\Components\Select::make('condition')
                                    ->options([
                                        'good' => 'Good',
                                        'spoiled' => 'Spoiled',
                                        'damaged' => 'Damaged',
                                    ])
                                    ->required(),
                            ])
                            ->columns(4)
                            ->minItems(1),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->sortable(),
                
                TextColumn::make('order.id')
                    ->label('Order')
                    ->searchable()
                    ->sortable(),
                
                TextColumn::make('buyer.name')
                    ->label('Buyer')
                    ->searchable()
                    ->sortable(),
                
                TextColumn::make('seller.shop_name')
                    ->label('Seller')
                    ->searchable()
                    ->sortable(),
                
                BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'approved',
                        'danger' => 'rejected',
                        'gray' => 'completed',
                        'info' => 'refunded',
                    ]),
                
                TextColumn::make('reason_type')
                    ->label('Reason')
                    ->searchable(),
                
                TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('RUB')
                    ->sortable(),
                
                TextColumn::make('refund_amount')
                    ->label('Refund')
                    ->money('RUB')
                    ->sortable(),
                
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                        'completed' => 'Completed',
                        'refunded' => 'Refunded',
                    ]),
                
                SelectFilter::make('reason_type')
                    ->options([
                        'spoiled' => 'Spoiled',
                        'wrong_item' => 'Wrong Item',
                        'changed_mind' => 'Changed Mind',
                        'damaged' => 'Damaged',
                        'other' => 'Other',
                    ]),
                
                Tables\Filters\TernaryFilter::make('is_cold_chain')
                    ->label('Cold Chain'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                
                Action::make('approve')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (Return $record): bool => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->action(function (Return $record) {
                        $service = app(\Modules\Supermarket\Application\Services\ReturnService::class);
                        $service->approve($record, auth()->id());
                    }),
                
                Action::make('reject')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn (Return $record): bool => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Rejection Reason')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (Return $record, array $data) {
                        $service = app(\Modules\Supermarket\Application\Services\ReturnService::class);
                        $service->reject($record, $data['reason'], auth()->id());
                    }),
                
                Action::make('complete')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Return $record): bool => $record->status === 'approved')
                    ->requiresConfirmation()
                    ->action(function (Return $record) {
                        $service = app(\Modules\Supermarket\Application\Services\ReturnService::class);
                        $service->complete($record);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    //
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            'items' => Tables\RelationManagers\RelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => \Modules\Supermarket\Filament\Resources\ReturnResource\Pages\ListReturns::route('/'),
            'create' => \Modules\Supermarket\Filament\Resources\ReturnResource\Pages\CreateReturn::route('/create'),
            'view' => \Modules\Supermarket\Filament\Resources\ReturnResource\Pages\ViewReturn::route('/{record}'),
            'edit' => \Modules\Supermarket\Filament\Resources\ReturnResource\Pages\EditReturn::route('/{record}/edit'),
        ];
    }
}
