<?php

declare(strict_types=1);

namespace Modules\Wallet\Presentation\Filament\Resources\WalletResource\Pages;

use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Modules\Wallet\Infrastructure\Models\WalletTransactionModel;
use Modules\Wallet\Presentation\Filament\Resources\WalletResource;

final class ViewWallet extends ViewRecord
{
    protected static string $resource = WalletResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Кошелёк')
                    ->schema([
                        TextEntry::make('holder_id')->label('Пользователь ID'),
                        TextEntry::make('balance')
                            ->label('Баланс')
                            ->formatStateUsing(fn (int $state): string => number_format($state / 100, 2) . ' ₽'),
                        TextEntry::make('meta.hold_amount')
                            ->label('Холд')
                            ->default(0)
                            ->formatStateUsing(fn ($state): string => number_format((int)$state / 100, 2) . ' ₽'),
                        TextEntry::make('updated_at')->label('Обновлён')->dateTime(),
                    ]),
            ]);
    }
}
