<?php

declare(strict_types=1);

namespace App\Domains\Wallet\Filament\Resources\WalletResource\Pages;

use App\Domains\Wallet\Filament\Resources\WalletResource;
use Filament\Resources\Pages\CreateRecord;

/**
 * Filament Page — создание кошелька.
 *
 * CANON 2026: Tenant-scoped (global scope на модели).
 * Никаких мусорных методов (__toString, isValid, VERSION, MAX_RETRIES).
 */
final class CreateWallet extends CreateRecord
{
    protected static string $resource = WalletResource::class;

    /** Редирект после создания — на список. */
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    /** Заголовок страницы. */
    public function getTitle(): string
    {
        return 'Создать кошелёк';
    }

    /** Мутация данных перед сохранением — добавляем uuid и correlation_id. */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['uuid'] = \Illuminate\Support\Str::uuid()->toString();
        $data['correlation_id'] = \Illuminate\Support\Str::uuid()->toString();

        return $data;
    }
}
