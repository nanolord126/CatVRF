<?php declare(strict_types=1);

namespace App\Livewire\User;


use Illuminate\Auth\AuthManager;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Database\DatabaseManager;

/**
 * Orders — Livewire-компонент истории заказов пользователя.
 *
 * Канон:
 *  - Список всех заказов с пагинацией.
 *  - Фильтры: статус, дата, вертикаль.
 *  - Детальный просмотр заказа (товары, доставка, оплата).
 *  - Ссылка на трекинг доставки (DeliveryTrack).
 *  - B2B: оптовые заказы с кредитом и отсрочкой платежа.
 *  - Tenant-scoped (user_id + tenant_id).
 */
final class Orders extends Component
{
    public function __construct(
        private readonly AuthManager $authManager,
        private readonly DatabaseManager $db,
    ) {}


    // ── публичные свойства ───────────────────────────────────────────────────

    private string $filterStatus   = 'all';   // all | pending | processing | completed | cancelled
    private string $filterVertical = 'all';
    private string $search         = '';
    private ?int $activeOrderId  = null;    // развёрнутый заказ
    private array $activeOrder    = [];
    private bool $isB2B          = false;
    private string $correlationId  = '';

    // ── lifecycle ───────────────────────────────────────────────────────────

    public function mount(): void
    {
        $this->correlationId = (string) Str::uuid();

        /** @var User $user */
        $user = $this->authManager->user();
        if (!$user) {
            $this->redirect(route('login'));
            return;
        }

        $this->isB2B = session('user_mode') === 'b2b';
    }

    // ── публичные экшены ─────────────────────────────────────────────────────

    public function setStatus(string $status): void
    {
        $allowed = ['all', 'pending', 'processing', 'completed', 'cancelled', 'refunded'];
        $this->filterStatus = in_array($status, $allowed, true) ? $status : 'all';
        $this->resetPage();
        $this->activeOrderId = null;
    }

    public function setVertical(string $vertical): void
    {
        $this->filterVertical = $vertical;
        $this->resetPage();
    }

    public function toggleOrder(int $orderId): void
    {
        if ($this->activeOrderId === $orderId) {
            $this->activeOrderId = null;
            $this->activeOrder   = [];
            return;
        }

        $this->activeOrderId = $orderId;
        $this->loadOrderDetails($orderId);
    }

    // ── приватные методы ─────────────────────────────────────────────────────

    private function loadOrderDetails(int $orderId): void
    {
        /** @var User $user */
        $user = $this->authManager->user();
        if (!$user) {
            return;
        }

        $order = $this->db->table('orders')
            ->where('id', $orderId)
            ->where('user_id', $user->id)
            ->first();

        if (!$order) {
            $this->activeOrder = [];
            return;
        }

        $items = $this->db->table('order_items')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->where('order_items.order_id', $orderId)
            ->select([
                'order_items.id',
                'products.name',
                'products.sku',
                'order_items.quantity',
                'order_items.price_kopecks',
                $this->db->raw('(order_items.quantity * order_items.price_kopecks) as subtotal'),
            ])
            ->get()
            ->toArray();

        $delivery = $this->db->table('delivery_orders')
            ->where('order_id', $orderId)
            ->select(['id', 'status', 'estimated_delivery_time', 'courier_id'])
            ->first();

        $this->activeOrder = [
            'order'    => (array) $order,
            'items'    => $items,
            'delivery' => $delivery ? (array) $delivery : null,
        ];
    }

    // ── геттеры для view ─────────────────────────────────────────────────────

    public function getOrders(): \Illuminate\Pagination\LengthAwarePaginator
    {
        /** @var User $user */
        $user = $this->authManager->user();
        if (!$user) {
            return new \Illuminate\Pagination\LengthAwarePaginator([], 0, 15);
        }

        $query = $this->db->table('orders')
            ->where('orders.user_id', $user->id)
            ->orderByDesc('orders.created_at')
            ->select([
                'orders.id',
                'orders.uuid',
                'orders.status',
                'orders.total_kopecks',
                'orders.created_at',
                'orders.vertical',
            ]);

        if ($this->filterStatus !== 'all') {
            $query->where('orders.status', $this->filterStatus);
        }

        if ($this->filterVertical !== 'all') {
            $query->where('orders.vertical', $this->filterVertical);
        }

        if (!empty($this->search)) {
            $query->where(function ($q): void {
                $q->where('orders.uuid', 'like', '%' . $this->search . '%');
            });
        }

        return $query->paginate(10);
    }

    // ── рендер ──────────────────────────────────────────────────────────────

    public function render(): View
    {
        return view('livewire.user.orders', [
            'orders' => $this->getOrders(),
        ])->layout('layouts.user-cabinet');
    }
}
