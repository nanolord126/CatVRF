<?php

namespace App\Filament\Tenant\Resources;

use App\Models\GiftCard;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Str;

class GiftCardResource extends Resource
{
    protected static ?string $model = GiftCard::class;
    protected static ?string $navigationGroup = 'Finance';

    public static function form(Form $form): Form {
        return $form->schema([
            Forms\Components\TextInput::make('code')->default(fn() => strtoupper(Str::random(12)))->unique(ignoreRecord: true)->required(),
            Forms\Components\TextInput::make('amount')->numeric()->required(),
            Forms\Components\TextInput::make('fee')->numeric()->default(3.00)->disabled(),
            Forms\Components\Select::make('status')->options(['pending' => 'Pending', 'active' => 'Active', 'used' => 'Used', 'expired' => 'Expired']),
        ]);
    }

    public static function table(Table $table): Table {
        return $table->columns([
            Tables\Columns\TextColumn::make('code'),
            Tables\Columns\TextColumn::make('amount')->money('RUB'),
            Tables\Columns\TextColumn::make('status')->badge(),
        ])
        ->actions([
            Action::make('activate')
                ->requiresConfirmation()
                ->action(fn (GiftCard $record) => self::activate($record))
                ->visible(fn (GiftCard $record) => $record->status === 'active'),
        ]);
    }

    public static function activate(GiftCard $card) {
        $user = auth()->user();
        if ($card->status !== 'active') return;
        $user->deposit($card->amount, ['description' => "Gift card {$card->code} activation"]);
        $card->update(['status' => 'used', 'activated_by' => $user->id]);
    }
}
