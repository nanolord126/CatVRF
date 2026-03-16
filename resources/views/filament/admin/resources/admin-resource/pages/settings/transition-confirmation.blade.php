<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section>
            <x-slot name="heading">
                Подтверждение переходаизменения
            </x-slot>

            <x-slot name="description">
                Пожалуйста, подтвердите это переходаизменение. Это действие не может быть отменено.
            </x-slot>

            <div class="space-y-4">
                <p class="text-sm text-gray-600">
                    Вы собираетесь перейти в новое состояние. Убедитесь, что все данные правильно сохранены перед продолжением.
                </p>

                <div class="flex gap-3">
                    <x-filament::button wire:click="confirm" color="success">
                        Подтвердить
                    </x-filament::button>

                    <x-filament::button wire:click="cancel" color="gray">
                        Отмена
                    </x-filament::button>
                </div>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
