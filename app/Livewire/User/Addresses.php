<?php

declare(strict_types=1);

namespace App\Livewire\User;

use Illuminate\Contracts\View\Factory as ViewFactory;

use Illuminate\Support\Collection;

use Illuminate\Auth\AuthManager;
use App\Models\User;
use App\Services\UserAddressService;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Component;
use Illuminate\Database\DatabaseManager;

/**
 * Addresses — Livewire-компонент управления сохранёнными адресами.
 *
 * Канон:
 *  - Максимум 5 адресов на пользователя.
 *  - При добавлении 6-го — удаляется наименее используемый.
 *  - Типы: home | work | other.
 *  - Установка адреса по умолчанию.
 *  - Геокодирование через Yandex Maps API (фронтенд).
 */
final class Addresses extends Component
{
    public const MAX_ADDRESSES = 5;
    // ── публичные свойства ───────────────────────────────────────────────────

    public array $addresses      = [];

    public bool $showForm       = false;

    public string $newAddress     = '';

    public string $newType        = 'home';     // home | work | other

    public string $newLat         = '';

    public string $newLon         = '';

    public ?int $defaultAddress = null;

    private string $errorMessage   = '';

    private string $correlationId  = '';

    private array $types = [
        'home'  => 'Дом',
        'work'  => 'Работа',
        'other' => 'Другое',
    ];

    // ── lifecycle ───────────────────────────────────────────────────────────

    public function __construct(private readonly ViewFactory $viewFactory,
        private readonly AuthManager $authManager,
        private readonly UserAddressService $addressService,
        private readonly DatabaseManager $db,) {}

    public function mount(): void
    {
        $this->correlationId = (string) Str::uuid();

        /** @var User $user */
        $user = $this->authManager->user();
        if (! $user) {
            $this->redirect(route('login'));

            return;
        }

        $this->loadAddresses($user);
    }

    // ── публичные экшены ─────────────────────────────────────────────────────

    public function toggleForm(): void
    {
        $this->showForm     = ! $this->showForm;
        $this->newAddress   = '';
        $this->newType      = 'home';
        $this->newLat       = '';
        $this->newLon       = '';
        $this->errorMessage = '';
    }

    public function save(): void
    {
        $this->validate([
            'newAddress' => 'required|string|min:5|max:500',
            'newType'    => 'required|in:home,work,other',
        ]);

        /** @var User $user */
        $user = $this->authManager->user();
        if (! $user) {
            return;
        }

        if (count($this->addresses) >= self::MAX_ADDRESSES) {
            // Удаляем наименее используемый
            $this->deleteOldest($user);
        }

        try {
            $address = $this->addressService->addOrGetAddress(
                $user->id,
                $this->newAddress,
                $this->newType
            );

            // Если переданы координаты — обновляем
            if ($this->newLat && $this->newLon) {
                $this->db->table('user_addresses')
                    ->where('id', $address->id)
                    ->update([
                        'lat' => (float) $this->newLat,
                        'lon' => (float) $this->newLon,
                    ]);
            }

            $this->loadAddresses($user);
            $this->toggleForm();
            $this->dispatch('address-saved', ['id' => $address->id]);

        } catch (\Throwable $e) {
            $this->errorMessage = 'Ошибка при сохранении адреса.';
        }
    }

    public function delete(int $addressId): void
    {
        /** @var User $user */
        $user = $this->authManager->user();
        if (! $user) {
            return;
        }

        $this->db->table('user_addresses')
            ->where('id', $addressId)
            ->where('user_id', $user->id)
            ->delete();

        $this->loadAddresses($user);
        $this->dispatch('address-deleted', ['id' => $addressId]);
    }

    public function setDefault(int $addressId): void
    {
        /** @var User $user */
        $user = $this->authManager->user();
        if (! $user) {
            return;
        }

        // Снимаем default со всех
        $this->db->table('user_addresses')
            ->where('user_id', $user->id)
            ->update(['is_default' => false]);

        // Устанавливаем default
        $this->db->table('user_addresses')
            ->where('id', $addressId)
            ->where('user_id', $user->id)
            ->update(['is_default' => true]);

        $this->defaultAddress = $addressId;
        $this->loadAddresses($user);
    }

    // ── рендер ──────────────────────────────────────────────────────────────

    public function render(): View
    {
        return $this->viewFactory->make('livewire.user.addresses')
            ->layout('layouts.user-cabinet');
    }

    // ── приватные методы ─────────────────────────────────────────────────────

    private function loadAddresses(User $user): void
    {
        $rows = $this->db->table('user_addresses')
            ->where('user_id', $user->id)
            ->orderByDesc('usage_count')
            ->limit(self::MAX_ADDRESSES)
            ->get();

        $this->addresses = $rows->map(fn (object $row): array => [
            'id'          => $row->id,
            'address'     => $row->address,
            'type'        => $row->type,
            'type_label'  => $this->types[$row->type] ?? $row->type,
            'usage_count' => $row->usage_count,
            'is_default'  => (bool) ($row->is_default ?? false),
            'lat'         => $row->lat ?? null,
            'lon'         => $row->lon ?? null,
        ])->toArray();

        $default = (new Collection($this->addresses))->firstWhere('is_default', true);
        $this->defaultAddress = $default ? (int) $default['id'] : null;
    }

    private function deleteOldest(User $user): void
    {
        $this->db->table('user_addresses')
            ->where('user_id', $user->id)
            ->where('is_default', false)  // не удаляем default
            ->orderBy('usage_count')
            ->limit(1)
            ->delete();
    }
}
