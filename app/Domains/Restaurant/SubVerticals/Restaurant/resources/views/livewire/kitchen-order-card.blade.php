@props([
    'order' => null,
    'highlight' => false,
])

<div 
    class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-4 border-l-4 {{ $highlight ? 'border-blue-500 ring-2 ring-blue-300' : 'border-gray-300' }} transition-all duration-300"
    x-data="{ 
        status: '{{ $order->status ?? 'pending' }}',
        key: 'kds-order-{{ $order->id ?? 0 }}',
        init() {
            const previous = localStorage.getItem(this.key);
            if (previous !== this.status) {
                this.$el.classList.add('animate-pulse');
                setTimeout(() => {
                    this.$el.classList.remove('animate-pulse');
                }, 600);
            }
            localStorage.setItem(this.key, this.status);
        }
    }"
    x-init="init()">
    
    <!-- Header -->
    <div class="flex justify-between items-start mb-3">
        <div>
            <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                Заказ #{{ $order->order_number ?? 'N/A' }}
            </h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Столик {{ $order->table->name ?? 'N/A' }}
            </p>
        </div>
        
        <x-status-badge 
            :status="$order->status ?? 'pending'"
            :label="match($order->status ?? 'pending') {
                'draft' => 'Черновик',
                'pending_payment' => 'Ожидает оплаты',
                'partially_paid' => 'Частично оплачен',
                'paid' => 'Оплачен',
                'in_kitchen' => 'На кухне',
                'ready' => 'Готов к выдаче',
                'completed' => 'Завершён',
                'cancelled' => 'Отменён',
                default => ucfirst($order->status ?? 'pending'),
            }"
            :color="match($order->status ?? 'pending') {
                'draft' => 'secondary',
                'pending_payment' => 'warning',
                'partially_paid' => 'amber',
                'paid' => 'success',
                'in_kitchen' => 'info',
                'ready' => 'emerald',
                'completed' => 'success',
                'cancelled' => 'danger',
                default => 'secondary',
            }"
            :icon="match($order->status ?? 'pending') {
                'draft' => 'document',
                'pending_payment' => 'clock',
                'partially_paid' => 'credit-card',
                'paid' => 'check-circle',
                'in_kitchen' => 'fire',
                'ready' => 'hand-raised',
                'completed' => 'check-badge',
                'cancelled' => 'x-circle',
                default => null,
            }"
        />
    </div>
    
    <!-- Order Items -->
    <div class="space-y-2 mb-3">
        @if(isset($order->items) && $order->items->count() > 0)
            @foreach($order->items as $item)
                <div class="flex justify-between items-center text-sm">
                    <span class="text-gray-700 dark:text-gray-300">
                        <span class="font-medium">{{ $item->quantity }}x</span>
                        {{ $item->name ?? 'Блюдо' }}
                    </span>
                    @if($item->notes)
                        <span class="text-xs text-gray-500 italic">{{ $item->notes }}</span>
                    @endif
                </div>
            @endforeach
        @else
            <p class="text-sm text-gray-500">Нет позиций</p>
        @endif
    </div>
    
    <!-- Footer -->
    <div class="flex justify-between items-center pt-3 border-t border-gray-200 dark:border-gray-700">
        <span class="text-sm text-gray-500 dark:text-gray-400">
            {{ $order->order_time?->format('H:i') ?? '--:--' }}
        </span>
        <span class="text-lg font-bold text-gray-900 dark:text-white">
            {{ number_format($order->total_amount ?? 0, 2) }} ₽
        </span>
    </div>
</div>
