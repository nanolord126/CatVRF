<div class="min-h-screen bg-gray-50">
    <div class="max-w-4xl mx-auto px-4 py-8">

        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">💳 Кошелёк</h1>
            <p class="text-gray-500 text-sm mt-1">Баланс, транзакции и бонусы</p>
        </div>

        {{-- Карточки балансов --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
            <div class="bg-gradient-to-br from-purple-500 to-purple-700 text-white rounded-2xl p-5 shadow">
                <p class="text-sm opacity-75 mb-1">Доступно</p>
                <p class="text-3xl font-bold">{{ number_format($available / 100, 2) }} ₽</p>
                @if($holdAmount > 0)
                    <p class="text-xs opacity-60 mt-2">Заморожено: {{ number_format($holdAmount / 100, 2) }} ₽</p>
                @endif
            </div>

            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                <p class="text-sm text-gray-500 mb-1">Общий баланс</p>
                <p class="text-2xl font-bold text-gray-800">{{ number_format($currentBalance / 100, 2) }} ₽</p>
            </div>

            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                <p class="text-sm text-gray-500 mb-1">Бонусы</p>
                <p class="text-2xl font-bold text-yellow-500">{{ number_format($bonusBalance / 100, 2) }} ✦</p>
            </div>
        </div>

        {{-- B2B: кредитный лимит --}}
        @if($isB2B && $creditLimit > 0)
            <div class="bg-blue-50 border border-blue-200 rounded-2xl p-5 mb-6">
                <p class="text-sm font-semibold text-blue-700 mb-2">Кредитный лимит B2B</p>
                <div class="flex items-center gap-4">
                    <div>
                        <p class="text-xs text-gray-500">Лимит</p>
                        <p class="text-lg font-bold text-gray-800">{{ number_format($creditLimit / 100, 2) }} ₽</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Использовано</p>
                        <p class="text-lg font-bold {{ $creditUsed / $creditLimit > 0.8 ? 'text-red-600' : 'text-gray-800' }}">
                            {{ number_format($creditUsed / 100, 2) }} ₽
                        </p>
                    </div>
                    <div class="flex-1">
                        <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                            <div class="h-full {{ $creditUsed / $creditLimit > 0.8 ? 'bg-red-500' : 'bg-blue-500' }} rounded-full"
                                 style="width: {{ $creditLimit > 0 ? round(min($creditUsed / $creditLimit * 100, 100)) : 0 }}%"></div>
                        </div>
                        <p class="text-xs text-gray-400 mt-1">{{ $creditLimit > 0 ? round($creditUsed / $creditLimit * 100) : 0 }}%</p>
                    </div>
                </div>
            </div>
        @endif

        {{-- Табы --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="border-b border-gray-100 flex">
                <button wire:click="setTab('money')"
                        class="px-6 py-3 text-sm font-medium transition border-b-2
                            {{ $activeTab === 'money' ? 'border-purple-500 text-purple-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                    Рубли
                </button>
                <button wire:click="setTab('bonuses')"
                        class="px-6 py-3 text-sm font-medium transition border-b-2
                            {{ $activeTab === 'bonuses' ? 'border-yellow-500 text-yellow-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                    Бонусы ✦
                </button>
            </div>

            {{-- Фильтры --}}
            <div class="p-4 border-b border-gray-50 flex gap-2 flex-wrap">
                @php
                    $types = $activeTab === 'money'
                        ? ['all' => 'Все', 'deposit' => 'Пополнение', 'withdrawal' => 'Списание', 'commission' => 'Комиссия', 'refund' => 'Возврат', 'payout' => 'Выплата']
                        : ['all' => 'Все', 'referral' => 'Реферал', 'turnover' => 'Оборот', 'promo' => 'Промо', 'loyalty' => 'Лояльность'];
                @endphp
                @foreach($types as $key => $label)
                    <button wire:click="setFilter('{{ $key }}')"
                            class="px-3 py-1 rounded-full text-xs font-medium transition
                                {{ $filterType === $key
                                    ? ($activeTab === 'money' ? 'bg-purple-100 text-purple-700' : 'bg-yellow-100 text-yellow-700')
                                    : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            {{-- Список транзакций --}}
            <div class="divide-y divide-gray-50">
                @forelse($this->getTransactions() as $tx)
                    <div class="flex items-center justify-between px-5 py-4 hover:bg-gray-50 transition">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-full flex items-center justify-center text-base
                                {{ in_array($tx->type, ['deposit', 'referral', 'turnover', 'promo', 'loyalty', 'bonus'])
                                    ? 'bg-green-100' : 'bg-red-100' }}">
                                {{ match($tx->type) {
                                    'deposit' => '⬆', 'withdrawal', 'commission', 'payout' => '⬇',
                                    'refund' => '↩', 'referral' => '👥', 'bonus' => '✦',
                                    'promo' => '🎁', 'loyalty' => '⭐', default => '●'
                                } }}
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-800">
                                    {{ match($tx->type) {
                                        'deposit' => 'Пополнение', 'withdrawal' => 'Списание',
                                        'commission' => 'Комиссия', 'refund' => 'Возврат',
                                        'payout' => 'Выплата', 'referral' => 'Реферальный бонус',
                                        'turnover' => 'Бонус с оборота', 'promo' => 'Промо-бонус',
                                        'loyalty' => 'Бонус лояльности', default => $tx->type
                                    } }}
                                </p>
                                <p class="text-xs text-gray-400">{{ $tx->created_at->diffForHumans() }}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-semibold
                                {{ in_array($tx->type, ['deposit', 'referral', 'turnover', 'promo', 'loyalty', 'bonus'])
                                    ? 'text-green-600' : 'text-red-500' }}">
                                {{ in_array($tx->type, ['deposit', 'referral', 'turnover', 'promo', 'loyalty', 'bonus']) ? '+' : '−' }}
                                {{ number_format($tx->amount / 100, 2) }}
                                {{ $activeTab === 'bonuses' ? '✦' : '₽' }}
                            </p>
                            <p class="text-xs text-gray-400">{{ $tx->correlation_id ? substr($tx->correlation_id, 0, 8) : '' }}</p>
                        </div>
                    </div>
                @empty
                    <div class="p-10 text-center text-gray-400 text-sm">Транзакций пока нет</div>
                @endforelse
            </div>

            {{-- Пагинация --}}
            <div class="px-5 py-4 border-t border-gray-100">
                {{ $this->getTransactions()->links() }}
            </div>
        </div>
    </div>
</div>
