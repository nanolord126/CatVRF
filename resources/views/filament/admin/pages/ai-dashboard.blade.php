<x-filament-panels::page>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        {{-- Total Embeddings Card --}}
        <div class="p-6 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
            <h3 class="text-sm font-medium text-gray-500">Всего нейронных эмбеддингов</h3>
            <p class="text-3xl font-bold mt-2 text-primary-600">{{ $totalEmbeddings }}</p>
        </div>

        {{-- AI Engine Status --}}
        <div class="p-6 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
            <h3 class="text-sm font-medium text-gray-500">Базовая модель (2026)</h3>
            <p class="text-3xl font-bold mt-2 text-green-600">Активно / gpt-4o-ext</p>
        </div>
    </div>

    <div class="mt-8">
        <h2 class="text-xl font-bold mb-4">Тепловая карта спроса по вертикалям (BigData)</h2>
        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm">
            <table class="w-full text-left">
                <thead>
                    <tr>
                        <th class="pb-3 border-b border-gray-100 dark:border-gray-700">Сущность вертикали</th>
                        <th class="pb-3 border-b border-gray-100 dark:border-gray-700">Взаимодействия (24ч)</th>
                        <th class="pb-3 border-b border-gray-100 dark:border-gray-700">Средний рейтинг доверия ИИ</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($demandStats as $stat)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                            <td class="py-4 border-b border-gray-100 dark:border-gray-700 font-medium">{{ strtoupper($stat->entity_type) }}</td>
                            <td class="py-4 border-b border-gray-100 dark:border-gray-700">{{ $stat->total }}</td>
                            <td class="py-4 border-b border-gray-100 dark:border-gray-700">0.992</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>
