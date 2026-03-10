<x-filament-panels::page>
    <div class="space-y-6">
        <div class="bg-gradient-to-r from-blue-600 to-indigo-600 rounded-xl p-8 text-white shadow-xl">
            <h2 class="text-3xl font-bold mb-2">Добро пожаловать в Экосистему 2026!</h2>
            <p class="text-lg opacity-90">Мы подготовили всё необходимое для запуска вашего бизнеса. Используйте кнопки ниже для быстрой настройки.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="flex items-center gap-4 mb-4">
                    <div class="p-3 bg-green-100 dark:bg-green-900 rounded-lg">
                        <x-heroicon-o-shield-check class="w-6 h-6 text-green-600 dark:text-green-400" />
                    </div>
                    <h3 class="text-xl font-semibold">Безопасность</h3>
                </div>
                <p class="text-gray-600 dark:text-gray-400 mb-6 text-sm line-clamp-2">Включите сквозное шифрование и интеграцию с Doppler для хранения секретов.</p>
                {{ $this->configure_dopplerAction }}
            </div>

            <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="flex items-center gap-4 mb-4">
                    <div class="p-3 bg-blue-100 dark:bg-blue-900 rounded-lg">
                        <x-heroicon-o-printer class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                    </div>
                    <h3 class="text-xl font-semibold">Фискализация</h3>
                </div>
                <p class="text-gray-600 dark:text-gray-400 mb-6 text-sm line-clamp-2">Подключите онлайн-кассу АТОЛ для автоматической печати чеков по ФЗ-54.</p>
                {{ $this->setup_atolAction }}
            </div>
        </div>
    </div>
</x-filament-panels::page>
