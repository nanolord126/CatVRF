<?php

declare(strict_types=1);

namespace App\Domains\Wallet\Filament\Resources\WalletResource\Pages;

use App\Domains\Wallet\Filament\Resources\WalletResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

/**
 * Filament Page — список кошельков.
 *
 * CANON 2026: Tenant-scoped (global scope на модели).
 * Никаких мусорных методов.
 */
final class ListWallets extends ListRecords
{
    protected static string $resource = WalletResource::class;

    /** Действия в заголовке — кнопка «Создать». */
    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    /** Заголовок страницы. */
    public function getTitle(): string
    {
        return 'Кошельки';
    }
}
