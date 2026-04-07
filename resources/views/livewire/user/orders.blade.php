<div class="min-h-screen bg-gray-50">
    <div class="max-w-4xl mx-auto px-4 py-8">

        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">📦 Мои заказы</h1>
            <p class="text-gray-500 text-sm mt-1">История покупок и статусы доставок</p>
        </div>

        {{-- Фильтры статуса --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 mb-4">
            <div class="flex gap-2 flex-wrap mb-3">
                @foreach([
                    'all' => 'Все',
                    'pending' => 'Ожидает',
                    'processing' => 'Обрабатывается',
                    'shipped' => 'Доставляется',
                    'completed' => 'Выполнен',
                    'cancelled' => 'Отменён',
                ] as $status => $label)
                    <button wire:click="setStatus('{{ $status }}')"
                            class="px-4 py-1.5 rounded-full text-xs font-medium transition
                                {{ $filterStatus === $status
                                    ? 'bg-gray-900 text-white'
                                    : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            <div class="flex gap-2 flex-wrap">
                {{-- Фильтр по вертикали --}}
                <select wire:model.live="filterVertical"
                        class="border border-gray-200 rounded-lg px-3 py-1.5 text-sm text-gray-600 focus:outline-none focus:border-purple-400">
                    <option value="">Все категории</option>
                    <option value="beauty">Красота</option>
                    <option value="furniture">Мебель</option>
                    <option value="food">Еда</option>
                    <option value="fashion">Мода</option>
                    <option value="fitness">Фитнес</option>
                    <option value="hotel">Отели</option>
                    <option value="travel">Путешествия</option>
                </select>

                {{-- Поиск --}}
                <div class="relative flex-1 min-w-40">
                    <input wire:model.live.debounce.400ms="search"
                           type="text" placeholder="Поиск по номеру заказа..."
                           class="w-full border border-gray-200 rounded-lg pl-3 pr-8 py-1.5 text-sm focus:outline-none focus:border-purple-400">
                    @if($search)
                        <button wire:click="$set('search', '')" class="absolute right-2 top-1.5 text-gray-400 hover:text-gray-600">✕</button>
                    @endif
                </div>
            </div>
        </div>

        {{-- Список заказов --}}
        <div class="space-y-3">
            @forelse($this->getOrders() as $order)
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                    {{-- Заголовок заказа --}}
                    <div class="flex items-center justify-between px-5 py-4 cursor-pointer hover:bg-gray-50 transition"
                         wire:click="toggleOrder({{ $order->id }})">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-purple-50 flex items-center justify-center text-sm">
                                {{ match($order->vertical ?? '') {
                                    'beauty' => '💄', 'furniture' => '🛋', 'food' => '🍽',
                                    'fashion' => '👗', 'fitness' => '💪', 'hotel' => '🏨',
                                    'travel' => '✈', default => '📦'
                                } }}
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-800">
                                    Заказ #{{ substr($order->uuid ?? $order->id, 0, 8) }}
                                </p>
                                <p class="text-xs text-gray-400">{{ $order->created_at->format('d.m.Y H:i') }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="px-3 py-1 rounded-full text-xs font-medium
                                @switch($order->status)
                                    @case('completed') bg-green-100 text-green-700 @break
                                    @case('cancelled') bg-red-100 text-red-700 @break
                                    @case('shipped') bg-blue-100 text-blue-700 @break
                                    @case('processing') bg-yellow-100 text-yellow-700 @break
                                    @default bg-gray-100 text-gray-600
                                @endswitch">
                                {{ match($order->status) {
                                    'pending' => 'Ожидает', 'processing' => 'Обрабатывается',
                                    'shipped' => 'В пути', 'completed' => 'Выполнен',
                                    'cancelled' => 'Отменён', default => $order->status
                                } }}
                            </span>
                            <span class="text-sm font-semibold text-gray-800">
                                {{ number_format($order->total_amount / 100, 2) }} ₽
                            </span>
                            <span class="text-gray-400 text-sm">{{ $activeOrderId === $order->id ? '▲' : '▼' }}</span>
                        </div>
                    </div>

                    {{-- Детали заказа (раскрываемые) --}}
                    @if($activeOrderId === $order->id && !empty($activeOrder))
                        <div class="border-t border-gray-100 px-5 py-4">
                            @if(!empty($activeOrder['items']))
                                <p class="text-xs text-gray-500 uppercase tracking-wide mb-3">Состав заказа</p>
                                <div class="space-y-2 mb-4">
                                    @foreach($activeOrder['items'] as $item)
                                        <div class="flex items-center justify-between py-2 border-b border-gray-50 last:border-0">
                                            <div class="flex items-center gap-3">
                                                @if(!empty($item['image']))
                                                    <img src="{{ $item['image'] }}" alt="{{ $item['name'] }}"
                                                         class="w-10 h-10 rounded-lg object-cover border border-gray-100">
                                                @else
                                                    <div class="w-10 h-10 rounded-lg bg-gray-100 flex items-center justify-center text-gray-400">📦</div>
                                                @endif
                                                <div>
                                                    <p class="text-sm text-gray-800">{{ $item['name'] ?? 'Товар' }}</p>
                                                    <p class="text-xs text-gray-400">× {{ $item['quantity'] ?? 1 }}</p>
                                                </div>
                                            </div>
                                            <p class="text-sm font-medium text-gray-800">
                                                {{ number_format(($item['price'] ?? 0) / 100, 2) }} ₽
                                            </p>
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            @if(!empty($activeOrder['delivery']))
                                <p class="text-xs text-gray-500 uppercase tracking-wide mb-2">Доставка</p>
                                <div class="flex items-center gap-2 text-sm text-gray-700 mb-4">
                                    <span>{{ match($activeOrder['delivery']['status'] ?? '') {
                                        'pending' => '⏳', 'assigned' => '🚴', 'in_transit' => '🚗',
                                        'delivered' => '✅', default => '📍'
                                    } }}</span>
                                    <span>{{ match($activeOrder['delivery']['status'] ?? '') {
                                        'pending' => 'Ожидает курьера', 'assigned' => 'Курьер назначен',
                                        'in_transit' => 'В пути', 'delivered' => 'Доставлено', default => 'Статус неизвестен'
                                    } }}</span>
                                    @if(!empty($activeOrder['delivery']['estimated_time']))
                                        <span class="text-gray-400">· ETA {{ $activeOrder['delivery']['estimated_time'] }}</span>
                                    @endif
                                </div>
                            @endif

                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-500">Итого:</span>
                                <span class="font-bold text-gray-900">{{ number_format($order->total_amount / 100, 2) }} ₽</span>
                            </div>
                        </div>
                    @endif
                </div>
            @empty
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-10 text-center text-gray-400">
                    <p class="text-4xl mb-3">📭</p>
                    <p class="text-sm">Заказов пока нет. Время что-нибудь купить!</p>
                </div>
            @endforelse
        </div>

        {{-- Пагинация --}}
        <div class="mt-6">
            {{ $this->getOrders()->links() }}
        </div>
    </div>
</div>
