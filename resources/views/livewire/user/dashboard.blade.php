<div class="min-h-screen bg-gray-50" x-data="{ mode: @entangle('userMode') }">

    {{-- Заголовок --}}
    <div class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 py-4 flex items-center justify-between">
            <h1 class="text-2xl font-bold text-gray-900">Личный кабинет</h1>

            {{-- Переключатель B2C / B2B --}}
            @if($isB2B)
            <div class="flex items-center gap-2">
                <button wire:click="switchMode('b2c')"
                        :class="mode === 'b2c' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-600'"
                        class="px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                    B2C
                </button>
                <button wire:click="switchMode('b2b')"
                        :class="mode === 'b2b' ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-600'"
                        class="px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                    B2B
                </button>
            </div>
            @endif
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 py-8">

        {{-- Карточки-метрики --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">

            {{-- Баланс кошелька --}}
            <div class="bg-white rounded-2xl shadow-sm p-6 border border-gray-100">
                <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">Баланс</p>
                <p class="text-3xl font-bold text-gray-900">{{ number_format($walletAvailable / 100, 2) }} ₽</p>
                @if($holdAmount > 0)
                    <p class="text-xs text-gray-400 mt-1">На удержании: {{ number_format($holdAmount / 100, 2) }} ₽</p>
                @endif
                <a href="{{ route('user.wallet') }}" class="text-blue-600 text-sm mt-3 inline-block hover:underline">Пополнить →</a>
            </div>

            {{-- Бонусы --}}
            <div class="bg-white rounded-2xl shadow-sm p-6 border border-gray-100">
                <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">Бонусы</p>
                <p class="text-3xl font-bold text-amber-500">{{ number_format($bonusBalance / 100, 2) }} ₽</p>
                <p class="text-xs text-gray-400 mt-1">Используйте при оплате</p>
            </div>

            {{-- Заказы --}}
            <div class="bg-white rounded-2xl shadow-sm p-6 border border-gray-100">
                <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">Заказы</p>
                <p class="text-3xl font-bold text-gray-900">{{ $ordersTotal }}</p>
                <a href="{{ route('user.orders') }}" class="text-blue-600 text-sm mt-3 inline-block hover:underline">Все заказы →</a>
            </div>

            {{-- AI-дизайны --}}
            <div class="bg-white rounded-2xl shadow-sm p-6 border border-gray-100">
                <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">AI-дизайны</p>
                <p class="text-3xl font-bold text-purple-600">{{ $aiDesignsTotal }}</p>
                <a href="{{ route('user.ai-constructor') }}" class="text-purple-600 text-sm mt-3 inline-block hover:underline">Создать →</a>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            {{-- Последние заказы --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-gray-800">Последние заказы</h2>
                    <a href="{{ route('user.orders') }}" class="text-blue-600 text-sm hover:underline">Все</a>
                </div>
                @forelse($recentOrders as $order)
                    <div class="flex items-center justify-between py-3 border-b border-gray-50 last:border-0">
                        <div>
                            <p class="text-sm font-medium text-gray-900">#{{ mb_strtoupper(mb_substr($order['uuid'] ?? '', 0, 8)) }}</p>
                            <p class="text-xs text-gray-500">{{ $order['created_at'] }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-semibold text-gray-800">{{ number_format($order['total'], 2) }} ₽</p>
                            <span class="text-xs px-2 py-0.5 rounded-full
                                @if($order['status'] === 'completed') bg-green-100 text-green-700
                                @elseif($order['status'] === 'cancelled') bg-red-100 text-red-700
                                @else bg-blue-100 text-blue-700 @endif">
                                {{ $order['status'] }}
                            </span>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-400">Заказов пока нет</p>
                @endforelse
            </div>

            {{-- Последние AI-дизайны --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-gray-800">AI-конструктор</h2>
                    <a href="{{ route('user.ai-constructor') }}" class="text-purple-600 text-sm hover:underline">Создать новый</a>
                </div>

                @forelse($recentDesigns as $design)
                    <div class="flex items-center gap-3 py-3 border-b border-gray-50 last:border-0">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-purple-400 to-pink-400 flex items-center justify-center text-white text-sm">
                            @switch($design['vertical'])
                                @case('beauty') 💄 @break
                                @case('furniture') 🛋 @break
                                @case('food') 🍽 @break
                                @case('fashion') 👗 @break
                                @case('fitness') 💪 @break
                                @case('hotel') 🏨 @break
                                @case('travel') ✈️ @break
                                @default 🤖
                            @endswitch
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-800">{{ ucfirst($design['vertical']) }}</p>
                            <p class="text-xs text-gray-500">{{ $design['created'] }}</p>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-6">
                        <p class="text-sm text-gray-400 mb-3">AI-дизайнов пока нет</p>
                        <a href="{{ route('user.ai-constructor') }}"
                           class="inline-flex items-center gap-2 bg-purple-600 text-white text-sm px-4 py-2 rounded-lg hover:bg-purple-700 transition">
                            🤖 Попробовать AI-конструктор
                        </a>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Быстрые ссылки --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6">
            <a href="{{ route('user.addresses') }}" class="bg-white rounded-xl p-4 border border-gray-100 hover:border-blue-300 transition text-center shadow-sm">
                <div class="text-2xl mb-2">📍</div>
                <p class="text-sm font-medium text-gray-700">Мои адреса</p>
            </a>
            <a href="{{ route('user.delivery-track') }}" class="bg-white rounded-xl p-4 border border-gray-100 hover:border-blue-300 transition text-center shadow-sm">
                <div class="text-2xl mb-2">🚚</div>
                <p class="text-sm font-medium text-gray-700">Отследить доставку</p>
            </a>
            <a href="{{ route('user.wallet') }}" class="bg-white rounded-xl p-4 border border-gray-100 hover:border-blue-300 transition text-center shadow-sm">
                <div class="text-2xl mb-2">💳</div>
                <p class="text-sm font-medium text-gray-700">Кошелёк</p>
            </a>
            <a href="{{ route('user.ai-constructor') }}" class="bg-white rounded-xl p-4 border border-gray-100 hover:border-purple-300 transition text-center shadow-sm">
                <div class="text-2xl mb-2">🤖</div>
                <p class="text-sm font-medium text-gray-700">AI-конструктор</p>
            </a>
        </div>
    </div>

    {{-- Livewire reload trigger --}}
    <div x-on:mode-switched.window="mode = $event.detail.mode"></div>
</div>
