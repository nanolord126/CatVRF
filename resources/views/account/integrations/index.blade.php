@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8" x-data="complianceManager()">
    <div class="md:grid md:grid-cols-3 md:gap-6">
        <div class="md:col-span-1">
            <div class="px-4 sm:px-0">
                <h3 class="text-lg font-medium text-gray-900">Регуляторные интеграции</h3>
                <p class="mt-1 text-sm text-gray-600">
                    Управление обязательными подключениями к государственным системам маркировки и прослеживаемости (Честный ЗНАК, Меркурий, ФГИС Зерно).
                </p>
            </div>
        </div>

        <div class="mt-5 md:mt-0 md:col-span-2">
            <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Система</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ИНН</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Статус</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Последняя проверка</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Действия</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @php
                            $availableIntegrations = [
                                'honest_sign' => 'Честный ЗНАК (Одежда/Обувь/Вода)',
                                'mercury' => 'Меркурий / ВетИС (Мясо/Рыба/Молоко)',
                                'mdlp' => 'МДЛП (Аптеки/Лекарства)',
                                'grain' => 'ФГИС Зерно (Мука/Крупы)',
                            ];
                        @endphp

                        @foreach($availableIntegrations as $type => $label)
                            @php
                                $integration = $integrations->firstWhere('type', $type);
                            @endphp
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $label }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $integration?->inn ?? '—' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($integration)
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $integration->status === 'connected' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                            {{ $integration->status === 'connected' ? 'Подключено' : 'Ошибка' }}
                                        </span>
                                    @else
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                            Отключено
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $integration?->last_checked_at?->diffForHumans() ?? 'Никогда' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <button @click="openModal('{{ $type }}', '{{ $label }}', '{{ $integration?->inn }}')" class="text-indigo-600 hover:text-indigo-900 mr-3">
                                        {{ $integration ? 'Изменить' : 'Подключить' }}
                                    </button>
                                    @if($integration)
                                        <button @click="disconnect('{{ $type }}')" class="text-red-600 hover:text-red-900">Отключить</button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- FAQ Accordion -->
            <div class="mt-10">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Часто задаваемые вопросы (FAQ)</h3>
                <div class="space-y-4" x-data="{ active: null }">
                    <div class="border rounded-lg bg-white shadow-sm overflow-hidden">
                        <button @click="active = (active === 1 ? null : 1)" class="w-full flex justify-between items-center p-4 text-left focus:outline-none bg-gray-50 hover:bg-gray-100 transition">
                            <span class="font-medium text-gray-700">Почему нельзя использовать ИНН маркетплейса?</span>
                            <svg class="w-5 h-5 transform transition" :class="active === 1 ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </button>
                        <div x-show="active === 1" x-collapse class="p-4 text-gray-600 text-sm border-t">
                            Согласно законодательству РФ (54-ФЗ, постановления о маркировке), ответственность за ввод товара в оборот и передачу сведений о маркировке несет собственник товара (продавец). Маркетплейс является лишь витриной или логистическим оператором. Штрафы за некорректную работу с маркировкой выставляются именно на ИНН владельца товара.
                        </div>
                    </div>

                    <div class="border rounded-lg bg-white shadow-sm overflow-hidden">
                        <button @click="active = (active === 2 ? null : 2)" class="w-full flex justify-between items-center p-4 text-left focus:outline-none bg-gray-50 hover:bg-gray-100 transition">
                            <span class="font-medium text-gray-700">Штрафы за отсутствие регистрации в 2025–2026?</span>
                            <svg class="w-5 h-5 transform transition" :class="active === 2 ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </button>
                        <div x-show="active === 2" x-collapse class="p-4 text-gray-600 text-sm border-t">
                            За продажу товаров без маркировки или с некорректной передачей сведений предусмотрены штрафы по КоАП РФ ст. 15.12: до 10 000 руб. для ИП и до 300 000 руб. для юридических лиц с конфискацией товара. В 2026 году контроль стал автоматизированным через ГИС МТ (Честный ЗНАК).
                        </div>
                    </div>

                    <div class="border rounded-lg bg-white shadow-sm overflow-hidden">
                        <button @click="active = (active === 3 ? null : 3)" class="w-full flex justify-between items-center p-4 text-left focus:outline-none bg-gray-50 hover:bg-gray-100 transition">
                            <span class="font-medium text-gray-700">Как получить токен для Честный ЗНАК?</span>
                            <svg class="w-5 h-5 transform transition" :class="active === 3 ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </button>
                        <div x-show="active === 3" x-collapse class="p-4 text-gray-600 text-sm border-t">
                            1. Зайдите в Личный кабинет системы Честный ЗНАК с помощью УКЭП.<br>
                            2. Перейдите в раздел «Профиль» -> «API».<br>
                            3. Сгенерируйте API-ключ (токен).<br>
                            4. Скопируйте его и вставьте в форму подключения на этой странице.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Component -->
    @include('components.modals.compliance-connect')
</div>

@push('scripts')
<script>
    function complianceManager() {
        return {
            isModalOpen: false,
            currentType: '',
            currentLabel: '',
            formData: {
                inn: '',
                api_token: ''
            },
            status: {
                loading: false,
                message: '',
                type: ''
            },

            openModal(type, label, inn = '') {
                this.currentType = type;
                this.currentLabel = label;
                this.formData.inn = inn;
                this.formData.api_token = '';
                this.isModalOpen = true;
                this.status.message = '';
            },

            async testConnection() {
                this.status.loading = true;
                this.status.message = 'Проверка подключения...';
                this.status.type = 'info';

                try {
                    const response = await fetch(`/account/integrations/${this.currentType}/test`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify(this.formData)
                    });
                    
                    const data = await response.json();
                    this.status.message = data.message;
                    this.status.type = data.success ? 'success' : 'error';
                } catch (e) {
                    this.status.message = 'Ошибка сети при проверке.';
                    this.status.type = 'error';
                } finally {
                    this.status.loading = false;
                }
            },

            async saveConnection() {
                this.status.loading = true;
                try {
                    const response = await fetch(`/account/integrations/${this.currentType}/connect`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify(this.formData)
                    });
                    
                    const data = await response.json();
                    if (data.success) {
                        window.location.reload();
                    } else {
                        this.status.message = data.message;
                        this.status.type = 'error';
                    }
                } catch (e) {
                    this.status.message = 'Ошибка при сохранении.';
                    this.status.type = 'error';
                } finally {
                    this.status.loading = false;
                }
            },

            async disconnect(type) {
                if (!confirm('Вы уверены, что хотите отключить интеграцию? Все настройки будут удалены.')) return;

                try {
                    const response = await fetch(`/account/integrations/${type}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        }
                    });
                    const data = await response.json();
                    if (data.success) window.location.reload();
                } catch (e) {
                    alert('Ошибка при отключении.');
                }
            }
        }
    }
</script>
@endpush
@endsection
