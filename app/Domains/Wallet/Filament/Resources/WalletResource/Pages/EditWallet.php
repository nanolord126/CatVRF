<?php

declare(strict_types=1);

namespace App\Domains\Wallet\Filament\Resources\WalletResource\Pages;

use App\Domains\Wallet\Filament\Resources\WalletResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

/**
 * Filament Page — редактирование кошелька.
 *
 * CANON 2026: Tenant-scoped (global scope на модели).
 * Никаких мусорных методов.
 */
final class EditWallet extends EditRecord
{
    protected static string $resource = WalletResource::class;

    /** Действия в заголовке. */
    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\ViewAction::make(),
        ];
    }

    /** Редирект после сохранения — на список. */
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    /** Заголовок страницы. */
    public function getTitle(): string
    {
        return 'Редактировать кошелёк #' . $this->record->getKey();
    }
}
