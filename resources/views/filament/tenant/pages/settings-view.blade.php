{{-- SettingsView — CatVRF 2026 --}}
<x-filament-panels::page>
    <div class="space-y-6">
        <div class="max-w-2xl">
            <div class="rounded-xl bg-white dark:bg-gray-900 p-6 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-4">Настройки бизнеса</h3>

                {{ $this->form }}

                <div class="mt-6 flex justify-end">
                    <x-filament::button
                        wire:click="save"
                        icon="heroicon-o-check"
                    >
                        Сохранить
                    </x-filament::button>
                </div>
            </div>
        </div>

        {{-- Additional Settings Sections --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Notifications --}}
            <div class="rounded-xl bg-white dark:bg-gray-900 p-5 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-4">Уведомления</h3>
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Email уведомления</span>
                        <span class="text-xs px-2 py-1 bg-green-100 text-green-700 rounded">Включено</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Push уведомления</span>
                        <span class="text-xs px-2 py-1 bg-green-100 text-green-700 rounded">Включено</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-400">SMS уведомления</span>
                        <span class="text-xs px-2 py-1 bg-gray-100 text-gray-700 rounded">Отключено</span>
                    </div>
                </div>
            </div>

            {{-- Integrations --}}
            <div class="rounded-xl bg-white dark:bg-gray-900 p-5 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-4">Интеграции</h3>
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Telegram</span>
                        <span class="text-xs px-2 py-1 bg-green-100 text-green-700 rounded">Подключено</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-400">WhatsApp</span>
                        <span class="text-xs px-2 py-1 bg-gray-100 text-gray-700 rounded">Не подключено</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-400">1C</span>
                        <span class="text-xs px-2 py-1 bg-gray-100 text-gray-700 rounded">Не подключено</span>
                    </div>
                </div>
            </div>

            {{-- API Keys --}}
            <div class="rounded-xl bg-white dark:bg-gray-900 p-5 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-4">API ключи</h3>
                <div class="space-y-3">
                    <div>
                        <p class="text-xs text-gray-500 mb-1">Public Key</p>
                        <code class="text-xs bg-gray-100 dark:bg-gray-800 px-2 py-1 rounded text-gray-600 dark:text-gray-400">
                            pk_live_••••••••••••••••
                        </code>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 mb-1">Secret Key</p>
                        <code class="text-xs bg-gray-100 dark:bg-gray-800 px-2 py-1 rounded text-gray-600 dark:text-gray-400">
                            sk_live_••••••••••••••••
                        </code>
                    </div>
                </div>
            </div>

            {{-- Security --}}
            <div class="rounded-xl bg-white dark:bg-gray-900 p-5 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-4">Безопасность</h3>
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-400">2FA</span>
                        <span class="text-xs px-2 py-1 bg-green-100 text-green-700 rounded">Включено</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-400">IP-ограничения</span>
                        <span class="text-xs px-2 py-1 bg-gray-100 text-gray-700 rounded">Отключено</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Аудит лог</span>
                        <span class="text-xs px-2 py-1 bg-green-100 text-green-700 rounded">Включено</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
