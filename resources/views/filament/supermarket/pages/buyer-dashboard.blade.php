<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Welcome -->
        <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg shadow p-6 text-white">
            <h2 class="text-2xl font-bold mb-2">Добро пожаловать, {{ Auth::user()->name }}!</h2>
            <p class="text-blue-100">У вас {{ number_format($bonuses, 0, ',', ' ') }} бонусов</p>
        </div>

        <!-- Active Subscriptions -->
        @if(!empty($activeSubscriptions))
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-4">Активные подписки</h3>
                <div class="space-y-4">
                    @foreach($activeSubscriptions as $subscription)
                        <div class="border rounded-xl p-4">
                            <div class="flex justify-between items-start mb-3">
                                <div>
                                    <h4 class="font-semibold">Подписка #{{ $subscription['id'] }}</h4>
                                    <p class="text-sm text-gray-600">
                                        {{ $subscription['frequency'] === 'weekly' ? 'Еженедельно' : 
                                            ($subscription['frequency'] === 'biweekly' ? 'Раз в 2 недели' : 'Ежемесячно') }}
                                    </p>
                                </div>
                                <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-xs font-medium">
                                    Активна
                                </span>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-600">Следующая доставка:</span>
                                <span class="font-medium">{{ \Carbon\Carbon::parse($subscription['next_delivery_at'])->format('d.m.Y') }}</span>
                            </div>
                            <div class="flex items-center justify-between text-sm mt-1">
                                <span class="text-gray-600">Сумма:</span>
                                <span class="font-semibold">{{ number_format($subscription['total_amount'], 0, ',', ' ') }} ₽</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Recent Orders -->
        @if(!empty($recentOrders))
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-4">Последние заказы</h3>
                <div class="space-y-3">
                    @foreach($recentOrders as $order)
                        <div class="flex items-center justify-between p-3 border rounded-lg">
                            <div>
                                <p class="font-medium">Заказ #{{ $order['id'] }}</p>
                                <p class="text-sm text-gray-600">{{ \Carbon\Carbon::parse($order['created_at'])->format('d.m.Y H:i') }}</p>
                            </div>
                            <div class="text-right">
                                <p class="font-semibold">{{ number_format($order['total_amount'], 0, ',', ' ') }} ₽</p>
                                <p class="text-sm text-gray-600">{{ $order['status'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
