<div class="kitchen-display h-screen bg-gray-900 text-white overflow-hidden">
    <!-- Header -->
    <div class="bg-gray-800 p-4 flex justify-between items-center border-b border-gray-700">
        <div class="flex items-center gap-4">
            <h1 class="text-2xl font-bold">{{ $stationType->label() }}</h1>
            <div class="flex gap-2">
                <button 
                    wire:click="setFilter('active')"
                    class="px-4 py-2 rounded {{ $filter === 'active' ? 'bg-blue-600' : 'bg-gray-700' }}"
                >
                    Активные ({{ count($orders) }})
                </button>
                <button 
                    wire:click="setFilter('completed')"
                    class="px-4 py-2 rounded {{ $filter === 'completed' ? 'bg-green-600' : 'bg-gray-700' }}"
                >
                    Готовы
                </button>
                <button 
                    wire:click="setFilter('all')"
                    class="px-4 py-2 rounded {{ $filter === 'all' ? 'bg-gray-600' : 'bg-gray-700' }}"
                >
                    Все
                </button>
            </div>
        </div>
        <div class="flex items-center gap-4">
            <span class="text-gray-400">Обновление: каждые {{ $refreshInterval }} сек</span>
            <button wire:click="loadOrders" class="bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded">
                Обновить
            </button>
        </div>
    </div>

    <!-- Orders Grid -->
    <div class="p-4 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 overflow-y-auto" style="max-height: calc(100vh - 80px);">
        @forelse($orders as $order)
            <div 
                class="order-card bg-gray-800 rounded-lg p-4 border-2 {{ $this->getCardBorder($order) }} transition-all"
                :class="{ 'animate-pulse': $order['is_overdue'] }"
            >
                <!-- Order Header -->
                <div class="flex justify-between items-start mb-3">
                    <div>
                        <span class="text-3xl font-bold">#{{ $order['order_id'] }}</span>
                        @if($order['is_vip'])
                            <span class="ml-2 bg-yellow-500 text-black px-2 py-1 rounded text-sm font-bold">VIP</span>
                        @endif
                        @if($order['is_from_marketplace'])
                            <span class="ml-2 bg-purple-500 px-2 py-1 rounded text-sm font-bold">MARKETPLACE</span>
                        @endif
                    </div>
                    <div class="text-right">
                        <span class="text-xl font-bold {{ $order['priority']['color'] }}">
                            {{ $order['priority']['label'] }}
                        </span>
                    </div>
                </div>

                <!-- Timer -->
                <div class="mb-3">
                    <div class="flex justify-between text-sm mb-1">
                        <span>Прошло: {{ $order['elapsed_minutes'] }} мин</span>
                        <span>Осталось: {{ $order['time_remaining'] }} мин</span>
                    </div>
                    <div class="w-full bg-gray-700 rounded-full h-4">
                        <div 
                            class="h-4 rounded-full {{ $this->getProgressColor($order['progress'], $order['is_overdue']) }}"
                            style="width: {{ $order['progress'] }}%"
                        ></div>
                    </div>
                    @if($order['is_overdue'])
                        <div class="text-red-500 font-bold mt-1 animate-pulse">ПРОСРОЧЕН!</div>
                    @endif
                </div>

                <!-- Items -->
                <div class="mb-3">
                    @foreach($order['items'] as $item)
                        <div class="flex justify-between mb-1">
                            <span class="text-lg">{{ $item['quantity'] }}x {{ $item['product_name'] }}</span>
                        </div>
                        @if(!empty($item['options']))
                            <div class="text-sm text-gray-400 ml-4">
                                {{ implode(', ', $item['options']) }}
                            </div>
                        @endif
                    @endforeach
                </div>

                <!-- Status & Actions -->
                <div class="border-t border-gray-700 pt-3 mt-3">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-lg font-bold {{ $this->getStatusColor($order['status']) }}">
                            {{ $order['status_label'] }}
                        </span>
                        <span class="text-sm text-gray-400">
                            {{ \Carbon\Carbon::parse($order['created_at'])->format('H:i') }}
                        </span>
                    </div>

                    @if($order['status'] === 'pending')
                        <button 
                            wire:click="updateOrderStatus({{ $order['id'] }}, 'in_progress')"
                            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded text-xl"
                        >
                            В РАБОТУ
                        </button>
                    @elseif($order['status'] === 'in_progress')
                        <button 
                            wire:click="updateOrderStatus({{ $order['id'] }}, 'ready')"
                            class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-4 rounded text-xl"
                        >
                            ГОТОВО
                        </button>
                        <button 
                            wire:click="reportProblem({{ $order['id'] }}, 'Проблема')"
                            class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded mt-2"
                        >
                            ПРОБЛЕМА
                        </button>
                    @endif

                    @if($order['problem_comment'])
                        <div class="mt-2 text-red-400 text-sm">
                            ⚠️ {{ $order['problem_comment'] }}
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <div class="col-span-full text-center text-gray-500 text-2xl py-20">
                Нет заказов для отображения
            </div>
        @endforelse
    </div>

    <!-- Problem Modal -->
    @if(isset($this->reportingOrderId))
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-gray-800 rounded-lg p-6 w-96">
                <h3 class="text-xl font-bold mb-4">Сообщить о проблеме</h3>
                <textarea 
                    wire:model="problemComment"
                    class="w-full bg-gray-700 text-white rounded p-2 mb-4"
                    rows="3"
                    placeholder="Опишите проблему..."
                ></textarea>
                <div class="flex gap-2">
                    <button 
                        wire:click="submitProblem"
                        class="flex-1 bg-red-600 hover:bg-red-700 py-2 rounded"
                    >
                        Отправить
                    </button>
                    <button 
                        wire:click="cancelProblem"
                        class="flex-1 bg-gray-600 hover:bg-gray-700 py-2 rounded"
                    >
                        Отмена
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Auto-refresh -->
    <script>
        setInterval(() => {
            @this.loadOrders();
        }, {{ $refreshInterval * 1000 }});
    </script>
</div>

@php
function getCardBorder($order)
{
    if ($order['is_overdue']) return 'border-red-500';
    if ($order['status'] === 'ready') return 'border-green-500';
    if ($order['status'] === 'in_progress') return 'border-blue-500';
    return 'border-gray-600';
}

function getProgressColor($progress, $isOverdue)
{
    if ($isOverdue) return 'bg-red-500';
    if ($progress >= 80) return 'bg-green-500';
    if ($progress >= 50) return 'bg-yellow-500';
    return 'bg-blue-500';
}

function getStatusColor($status)
{
    return match($status) {
        'pending' => 'text-gray-400',
        'in_progress' => 'text-blue-400',
        'ready' => 'text-green-400',
        'problem' => 'text-red-400',
        default => 'text-gray-400',
    };
}
@endphp
