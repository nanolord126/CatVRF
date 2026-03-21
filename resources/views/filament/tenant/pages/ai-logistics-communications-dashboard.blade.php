<x-filament-panels::page>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <!-- AI Supply KPI -->
        <x-filament::section>
            <x-slot name="heading">ИИ-Прогнозирование перераспределения запасов (Поток предложений)</x-slot>
            <x-slot name="description">Мониторинг всплесков локального спроса и предлагаемых перемещений запасов для предотвращения их нехватки в ключевых точках.</x-slot>
            
            {{ $this->table }}
        </x-filament::section>

        <!-- Smart Communications (AI Personalized Outreach) -->
        <x-filament::section>
            <x-slot name="heading">Цифровой охват: Умная очередь уведомлений (Ближайшие 24 часа)</x-slot>
            <x-slot name="description">Сообщения, ожидающие рассчитанного ИИ "Идеального момента" на основе паттернов поведения пользователей.</x-slot>

            <div class="space-y-4">
                @foreach($this->getViewData()['notifications_queue'] as $notification)
                    <div class="p-4 border rounded-lg dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
                        <div class="flex justify-between items-center mb-2">
                            <span class="font-bold text-lg text-primary-600 truncate max-w-[70%]">{{ $notification->title }}</span>
                            <span class="badge {{ $notification->urgency_score > 0.8 ? 'badge-danger' : 'badge-info' }}">
                                {{ $notification->urgency_score > 0.8 ? 'Срочно' : 'Обычно' }}
                            </span>
                        </div>
                        <div class="text-xs opacity-70 mb-2">Контекст: {{ $notification->trigger_context }} | Запланировано: {{ $notification->scheduled_send_at }}</div>
                        <div class="text-sm line-clamp-2 italic">"{{ $notification->message }}"</div>
                        <div class="mt-4 flex items-center gap-2">
                             <div class="h-1 bg-primary-600 rounded-full" style="width: {{ $notification->urgency_score * 100 }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
