<x-filament-widgets::widget>
    @php
        $data = $this->getProfileData();
        $vertical = $data['vertical'];
        $profile = $data['profile'];
        $fields = $data['fields'];

        $verticalLabels = [
            'beauty' => '💇 Красота',
            'hotel' => '🏨 Отели',
            'flowers' => '💐 Цветы',
            'auto' => '🚗 Авто',
            'food' => '🍕 Еда',
            'furniture' => '🛋 Мебель',
            'fashion' => '👗 Мода',
            'fitness' => '💪 Фитнес',
            'real_estate' => '🏠 Недвижимость',
            'medical' => '⚕️ Медицина',
            'education' => '📚 Образование',
            'travel' => '✈️ Путешествия',
            'pet' => '🐾 Питомцы',
            'taxi' => '🚕 Такси',
            'electronics' => '📱 Электроника',
            'events' => '🎉 Мероприятия',
        ];
    @endphp

    <x-filament::section
        :heading="($verticalLabels[$vertical] ?? 'Профиль вертикали') . ' — профиль'"
        icon="heroicon-o-adjustments-horizontal"
        collapsible
    >
        @if ($profile === null)
            <div class="text-sm text-gray-500 dark:text-gray-400">
                Профиль вертикали не заполнен.
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach ($fields as $label => $value)
                    <div class="space-y-1">
                        <dt class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            {{ $label }}
                        </dt>
                        <dd class="text-sm text-gray-900 dark:text-gray-100">
                            @if (str_contains($value, "\n"))
                                <pre class="text-xs bg-gray-50 dark:bg-gray-800 p-2 rounded-lg overflow-auto max-h-32">{{ $value }}</pre>
                            @else
                                {{ $value }}
                            @endif
                        </dd>
                    </div>
                @endforeach
            </div>

            <div class="mt-3 text-xs text-gray-400">
                Обновлено: {{ $profile->updated_at?->diffForHumans() ?? '–' }}
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
