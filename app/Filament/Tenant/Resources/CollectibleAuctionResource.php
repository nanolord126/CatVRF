<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use App\Models\Collectibles\CollectibleAuction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Services\Collectibles\AuctionService;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * CollectibleAuctionResource — Real-time management for rare auctions.
 * Features auction controls and automated finalization.
 */
class CollectibleAuctionResource extends Resource
{
    protected static ?string $model = CollectibleAuction::class;
    protected static ?string $navigationIcon = 'heroicon-o-clock';
    protected static ?string $navigationGroup = 'Collectibles Hub';
    protected static ?string $tenantOwnershipRelationshipName = 'store';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Auction Setup')->schema([
                Forms\Components\Select::make('item_id')->relationship('item', 'name')->required(),
                Forms\Components\Select::make('store_id')->relationship('store', 'name')->required(),
                Forms\Components\Select::make('status')->options([
                    'Scheduled' => 'Scheduled', 'Active' => 'Active', 'Completed' => 'Completed', 'Cancelled' => 'Cancelled'
                ])->required()->default('Scheduled'),
            ])->columns(2),

            Forms\Components\Section::make('Pricing & Timing')->schema([
                Forms\Components\TextInput::make('start_price_cents')->numeric()->prefix('RUB')->required(),
                Forms\Components\TextInput::make('reserve_price_cents')->numeric()->prefix('RUB')->nullable(),
                Forms\Components\DateTimePicker::make('start_at')->required(),
                Forms\Components\DateTimePicker::make('end_at')->required(),
            ])->columns(2),

            Forms\Components\Section::make('Current Bid Info')->schema([
                Forms\Components\TextInput::make('current_bid_cents')->numeric()->prefix('RUB')->disabled(),
                Forms\Components\TextInput::make('winner_id')->disabled(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('item.name')->searchable()->sortable(),
            Tables\Columns\BadgeColumn::make('status')
                ->colors([
                    'gray' => 'Scheduled', 'green' => 'Active', 'success' => 'Completed', 'danger' => 'Cancelled'
                ]),
            Tables\Columns\TextColumn::make('start_price_cents')->money('rub', divideBy: 100)->label('Start'),
            Tables\Columns\TextColumn::make('current_bid_cents')->money('rub', divideBy: 100)->label('Current Bid'),
            Tables\Columns\TextColumn::make('end_at')->dateTime()->sortable(),
        ])->actions([
            Tables\Actions\EditAction::make(),
            Action::make('finalize')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->action(function (CollectibleAuction $record, AuctionService $service) {
                    try {
                        $service->finalizeAuction($record->id);
                        Notification::make()->title("Auction finalized successfully.")->success()->send();
                    } catch (\Throwable $e) {
                        Notification::make()->title("Finalization failed: " . $e->getMessage())->danger()->send();
                        Log::channel('audit')->error("Auction Finalize Action failed", [
                            'auction_id' => $record->id,
                            'error' => $e->getMessage()
                        ]);
                    }
                }),
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['item', 'store'])->latest('end_at');
    }
}
