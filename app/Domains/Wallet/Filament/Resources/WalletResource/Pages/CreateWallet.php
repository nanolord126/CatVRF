<?php

declare(strict_types=1);

namespace App\Domains\Wallet\Filament\Resources\WalletResource\Pages;

use App\Domains\Wallet\Filament\Resources\WalletResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

/**
 * Filament Page — создание кошелька.
 *
 * CANON 2026: Tenant-scoped (global scope на модели).
 * Никаких мусорных методов (__toString, isValid, VERSION, MAX_RETRIES).
 */
final class CreateWallet extends CreateRecord
{
    protected static string $resource = WalletResource::class;

    /** Заголовок страницы. */
    public function getTitle(): string
    {
        return 'Создать кошелёк';
    }

    /** Редирект после создания — на список. */
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    /** Мутация данных перед сохранением — добавляем uuid и correlation_id. */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['uuid'] = Str::uuid()->toString();
        $data['correlation_id'] = Str::uuid()->toString();

        return $data;
    }
}
