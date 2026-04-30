<x-slot name="title">
    Личный кабинет тренера
</x-slot>

<div class="space-y-6">
    @if($isOnHold)
        <div class="bg-red-50 border-l-4 border-red-400 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-red-700">
                        <strong>Ваш аккаунт на удержании:</strong> {{ $onHoldReason }}
                    </p>
                </div>
            </div>
        </div>
    @endif

    @if(!empty($upcomingExpirations))
        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-yellow-700">
                        <strong>Предупреждение:</strong> Скоро истекают сертификаты или специализации
                    </p>
                    <ul class="mt-2 text-sm text-yellow-700 list-disc list-inside">
                        @foreach($upcomingExpirations as $item)
                            <li>{{ $item['name'] }} ({{ $item['type'] }}) - {{ $item['expiry_date'] }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    <!-- Qualification Level -->
    <div class="bg-white shadow rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Квалификация</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-gray-50 p-4 rounded-lg">
                <p class="text-sm text-gray-500">Уровень</p>
                <p class="text-2xl font-bold text-gray-900">{{ ucfirst($qualificationLevel) }}</p>
            </div>
            <div class="bg-gray-50 p-4 rounded-lg">
                <p class="text-sm text-gray-500">Статус</p>
                <p class="text-2xl font-bold {{ $isOnHold ? 'text-red-600' : 'text-green-600' }}">
                    {{ $isOnHold ? 'На удержании' : 'Активен' }}
                </p>
            </div>
            <div class="bg-gray-50 p-4 rounded-lg">
                <p class="text-sm text-gray-500">Специализаций</p>
                <p class="text-2xl font-bold text-gray-900">{{ count($specializations) }}</p>
            </div>
        </div>
    </div>

    <!-- Effectiveness -->
    @if(!empty($effectivenessData))
        <div class="bg-white shadow rounded-lg p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-gray-900">Эффективность</h3>
                <button wire:click="requestEffectivenessRecalculation" class="text-sm text-blue-600 hover:text-blue-800">
                    Обновить данные
                </button>
            </div>
            
            <div class="mb-4">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-500">Общий балл</span>
                    <span class="text-3xl font-bold {{ $effectivenessData['is_top_performer'] ? 'text-green-600' : ($effectivenessData['is_critical'] ? 'text-red-600' : 'text-gray-900') }}">
                        {{ number_format($effectivenessData['total_score'], 1) }}
                    </span>
                </div>
                <div class="mt-2">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                        {{ $effectivenessData['is_top_performer'] ? 'bg-green-100 text-green-800' : 
                           ($effectivenessData['requires_attention'] ? 'bg-yellow-100 text-yellow-800' : 
                           ($effectivenessData['is_critical'] ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800')) }}">
                        {{ $effectivenessData['effectiveness_level'] }}
                    </span>
                </div>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-gray-50 p-3 rounded-lg">
                    <p class="text-xs text-gray-500">Удержание</p>
                    <p class="text-lg font-semibold">{{ number_format($effectivenessData['retention_rate'], 1) }}%</p>
                </div>
                <div class="bg-gray-50 p-3 rounded-lg">
                    <p class="text-xs text-gray-500">NPS</p>
                    <p class="text-lg font-semibold">{{ number_format($effectivenessData['nps_score'], 1) }}</p>
                </div>
                <div class="bg-gray-50 p-3 rounded-lg">
                    <p class="text-xs text-gray-500">Средний чек</p>
                    <p class="text-lg font-semibold">{{ number_format($effectivenessData['avg_check'], 0) }} ₽</p>
                </div>
                <div class="bg-gray-50 p-3 rounded-lg">
                    <p class="text-xs text-gray-500">Загрузка</p>
                    <p class="text-lg font-semibold">{{ number_format($effectivenessData['occupancy_rate'], 1) }}%</p>
                </div>
            </div>

            <p class="mt-4 text-xs text-gray-400">Обновлено: {{ $effectivenessData['period_end'] }}</p>
        </div>
    @endif

    <!-- Certifications -->
    <div class="bg-white shadow rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Сертификаты</h3>
        @if(empty($certifications))
            <p class="text-gray-500">Нет активных сертификатов</p>
        @else
            <div class="space-y-3">
                @foreach($certifications as $cert)
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div>
                            <p class="font-medium text-gray-900">{{ $cert['name'] }}</p>
                            <p class="text-sm text-gray-500">{{ $cert['issuer'] }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm {{ $cert['is_expiring_soon'] ? 'text-yellow-600 font-medium' : 'text-gray-600' }}">
                                {{ $cert['expiry_date'] ?? 'Бессрочно' }}
                            </p>
                            @if($cert['is_expiring_soon'])
                                <p class="text-xs text-yellow-600">Осталось {{ $cert['days_until_expiry'] }} дн.</p>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Specializations -->
    <div class="bg-white shadow rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Специализации</h3>
        @if(empty($specializations))
            <p class="text-gray-500">Нет активных специализаций</p>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                @foreach($specializations as $spec)
                    <div class="p-3 bg-gray-50 rounded-lg {{ $spec['is_expired'] ? 'border border-red-300' : '' }}">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="font-medium text-gray-900">{{ $spec['specialization'] }}</p>
                                <p class="text-sm text-gray-500">{{ ucfirst($spec['level']) }}</p>
                            </div>
                            @if($spec['is_expired'])
                                <span class="text-xs text-red-600">Истекла</span>
                            @elseif($spec['expiry_date'])
                                <span class="text-xs text-gray-500">{{ $spec['expiry_date'] }}</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

@script
<script>
    Livewire.on('notification', (message, type) => {
        alert(message);
    });
</script>
@endscript
