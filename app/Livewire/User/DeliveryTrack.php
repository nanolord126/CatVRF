<?php declare(strict_types=1);

namespace App\Livewire\User;


use Illuminate\Auth\AuthManager;
use App\Models\User;
use App\Services\Delivery\GeotrackingService;
use Illuminate\Support\Facades\Auth;

use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Component;
use Illuminate\Database\DatabaseManager;

/**
 * DeliveryTrack — реал-тайм трекинг доставки в личном кабинете.
 *
 * Канон:
 *  - Пользователь видит только свою доставку.
 *  - Реал-тайм через Laravel Echo (private channel delivery.{orderId}).
 *  - Карта — Leaflet + OpenStreetMap / Yandex Maps.
 *  - Обновление позиции курьера каждые 3 секунды через WebSocket.
 *  - Показывает: статус, ETA, текущие координаты, timeline.
 */
final class DeliveryTrack extends Component
{
    // ── публичные свойства ───────────────────────────────────────────────────

    private int $deliveryOrderId = 0;
    private string $status          = '';
    private float $courierLat      = 0.0;
    private float $courierLon      = 0.0;
    private float $pickupLat       = 0.0;
    private float $pickupLon       = 0.0;
    private float $deliveryLat     = 0.0;
    private float $deliveryLon     = 0.0;
    private string $estimatedTime   = '';
    private array $timeline        = [];
    private bool $isDelivered     = false;
    private string $correlationId   = '';

    /** Список активных доставок пользователя для выбора */
    private array $activeDeliveries = [];

    // ── lifecycle ───────────────────────────────────────────────────────────

    public function __construct(
        private readonly AuthManager $authManager,
        private GeotrackingService $geotracking,
        private readonly DatabaseManager $db,
    ) {}

    public function mount(?int $deliveryOrderId = null): void
    {
        $this->correlationId = (string) Str::uuid();

        /** @var User $user */
        $user = $this->authManager->user();
        if (!$user) {
            $this->redirect(route('login'));
            return;
        }

        $this->loadActiveDeliveries($user);

        if ($deliveryOrderId) {
            $this->selectDelivery($deliveryOrderId);
        } elseif (!empty($this->activeDeliveries)) {
            $this->selectDelivery((int) $this->activeDeliveries[0]['id']);
        }
    }

    // ── публичные экшены ─────────────────────────────────────────────────────

    /**
     * Выбрать заказ для трекинга.
     */
    public function selectDelivery(int $deliveryOrderId): void
    {
        /** @var User $user */
        $user = $this->authManager->user();
        if (!$user) {
            return;
        }

        // Проверяем, что заказ принадлежит пользователю
        $delivery = $this->db->table('delivery_orders as d')
            ->join('orders as o', 'o.id', '=', 'd.order_id')
            ->where('d.id', $deliveryOrderId)
            ->where('o.user_id', $user->id)
            ->select([
                'd.id', 'd.status',
                'd.pickup_location', 'd.delivery_location',
                'd.estimated_delivery_time', 'd.courier_id',
            ])
            ->first();

        if (!$delivery) {
            return;
        }

        $this->deliveryOrderId = $deliveryOrderId;
        $this->status          = $delivery->status;
        $this->estimatedTime   = $delivery->estimated_delivery_time ?? '';
        $this->isDelivered     = $delivery->status === 'delivered';

        // Координаты pickup/delivery
        $pickup   = json_decode($delivery->pickup_location, true) ?? [];
        $dest     = json_decode($delivery->delivery_location, true) ?? [];
        $this->pickupLat   = (float) ($pickup['lat']  ?? 0);
        $this->pickupLon   = (float) ($pickup['lon']  ?? 0);
        $this->deliveryLat = (float) ($dest['lat']    ?? 0);
        $this->deliveryLon = (float) ($dest['lon']    ?? 0);

        // Текущая позиция курьера
        if ($delivery->courier_id) {
            $courier = $this->db->table('couriers')
                ->where('id', $delivery->courier_id)
                ->select('current_location')
                ->first();

            if ($courier && $courier->current_location) {
                $loc = json_decode($courier->current_location, true) ?? [];
                $this->courierLat = (float) ($loc['lat'] ?? 0);
                $this->courierLon = (float) ($loc['lon'] ?? 0);
            }
        }

        $this->buildTimeline($delivery->status);

        // Подписываемся на Echo-канал через JS (dispatch)
        $this->dispatch('subscribe-tracking', ['deliveryOrderId' => $deliveryOrderId]);
    }

    /**
     * Обновление позиции курьера — вызывается из JavaScript через Echo.
     */
    public function updateCourierPosition(float $lat, float $lon): void
    {
        $this->courierLat = $lat;
        $this->courierLon = $lon;
    }

    /**
     * Обновление статуса доставки — вызывается из JavaScript через Echo.
     */
    public function updateStatus(string $status): void
    {
        $this->status      = $status;
        $this->isDelivered = $status === 'delivered';
        $this->buildTimeline($status);
    }

    // ── приватные методы ─────────────────────────────────────────────────────

    private function loadActiveDeliveries(User $user): void
    {
        $this->activeDeliveries = $this->db->table('delivery_orders as d')
            ->join('orders as o', 'o.id', '=', 'd.order_id')
            ->where('o.user_id', $user->id)
            ->whereIn('d.status', ['pending', 'assigned', 'picked_up', 'in_transit'])
            ->select(['d.id', 'd.status', 'o.id as order_id', 'o.created_at'])
            ->orderByDesc('d.id')
            ->limit(5)
            ->get()
            ->map(fn(object $row): array => [
                'id'       => $row->id,
                'order_id' => $row->order_id,
                'status'   => $row->status,
                'created'  => $row->created_at,
            ])
            ->toArray();
    }

    private function buildTimeline(string $status): void
    {
        $steps = [
            'pending'    => 'Ожидает курьера',
            'assigned'   => 'Курьер назначен',
            'picked_up'  => 'Забрал у продавца',
            'in_transit' => 'В пути',
            'delivered'  => 'Доставлен',
        ];

        $order = array_keys($steps);
        $currentIndex = array_search($status, $order, true);

        $this->timeline = [];
        foreach ($steps as $key => $label) {
            $idx = array_search($key, $order, true);
            $this->timeline[] = [
                'key'       => $key,
                'label'     => $label,
                'completed' => $idx !== false && $currentIndex !== false && $idx <= $currentIndex,
                'active'    => $key === $status,
            ];
        }
    }

    // ── рендер ──────────────────────────────────────────────────────────────

    /**
     * Listeners для Echo — обновление данных из JavaScript.
     */
    protected $listeners = [
        'courier-location-updated' => 'updateCourierPosition',
        'delivery-status-updated'  => 'updateStatus',
    ];

    public function render(): View
    {
        return view('livewire.user.delivery-track')
            ->layout('layouts.user-cabinet');
    }
}
