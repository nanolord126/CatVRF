<x-filament-panels::page>
    <div class="space-y-6">
        <header class="flex items-center justify-between border-b pb-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Персональный План Здоровья</h1>
                <p class="text-sm text-gray-500">Ваш ежедневный и еженедельный чеклист на основе медицинских рекомендаций и напоминаний для ваших питомцев.</p>
            </div>
            <div class="flex items-center space-x-2">
                <span class="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">
                    AI Enabled: ✅
                </span>
                <span class="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-600/20">
                    Sync Status: OK
                </span>
            </div>
        </header>

        @if(count($this->tasks) === 0)
            <div class="flex flex-col items-center justify-center rounded-xl bg-white p-12 text-center shadow-sm dark:bg-gray-900 border border-gray-100 dark:border-gray-800">
                <div class="mb-4 rounded-full bg-blue-50 p-3 text-blue-600 dark:bg-blue-900/20">
                    <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Все задачи выполнены!</h3>
                <p class="mt-2 text-sm text-gray-500">На сегодня больше нет рекомендаций. Отдыхайте!</p>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($this->tasks as $task)
                    <div class="group relative flex flex-col justify-between overflow-hidden rounded-xl bg-white p-5 shadow-sm transition-all hover:shadow-md dark:bg-gray-800 border border-gray-100 dark:border-gray-700">
                        <div class="flex items-start justify-between">
                            <div class="flex items-center space-x-3">
                                <span @class([
                                    'h-3 w-3 rounded-full',
                                    'bg-red-500' => $task->frequency === 'DAILY',
                                    'bg-yellow-500' => $task->frequency === 'WEEKLY',
                                    'bg-blue-500' => $task->frequency === 'MONTHLY',
                                    'bg-gray-400' => $task->frequency === 'ONCE',
                                ])></span>
                                <span class="text-xs font-bold uppercase tracking-wider text-gray-400">
                                    {{ $task->frequency }}
                                </span>
                            </div>
                            @if($task->target_type === 'ANIMAL')
                                <span title="Для питомца" class="inline-flex items-center rounded-md bg-purple-50 px-2 py-0.5 text-[10px] font-medium text-purple-700 ring-1 ring-inset ring-purple-600/20 animate-pulse">
                                    🐾 PET CARE
                                </span>
                            @endif
                        </div>

                        <div class="mt-3">
                            <h3 class="text-base font-bold text-gray-900 dark:text-gray-100 group-hover:text-primary-600 transition-colors">
                                {{ $task->title }}
                            </h3>
                            <p class="mt-1 text-sm text-gray-500 line-clamp-2">
                                {{ $task->description }}
                            </p>
                        </div>

                        <div class="mt-4 flex items-center justify-between border-t border-gray-50 pt-4 dark:border-gray-700">
                            <div class="flex flex-col">
                                <span class="text-[10px] text-gray-400 uppercase font-semibold">Срок:</span>
                                <span class="text-xs font-medium text-gray-700 dark:text-gray-300">
                                    {{ $task->next_due_date->format('d.m.Y') }}
                                </span>
                            </div>
                            
                            <button 
                                wire:click="toggleComplete({{ $task->id }})"
                                class="inline-flex items-center justify-center rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600 transition-all active:scale-95"
                            >
                                <svg class="mr-1.5 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                </svg>
                                Выполнить
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        <div class="mt-8 rounded-xl bg-gray-50 p-4 dark:bg-gray-900 border-l-4 border-primary-500">
            <h4 class="text-sm font-bold text-gray-900 dark:text-gray-100 flex items-center">
                <svg class="mr-2 h-4 w-4 text-primary-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                </svg>
                AI Insights: Как это работает?
            </h4>
            <p class="mt-1 text-xs text-gray-600 dark:text-gray-400 leading-relaxed">
                Наш AI-движок анализирует раздел "Назначение" (Prescription) в каждой вашей медицинской карте (человеческой или ветеринарной). 
                Он автоматически разбивает длинные списки лекарств на ежедневные задачи и отправляет их в ваш чеклист. 
                Если вы пропустите прием, AI скорректирует следующую дату или уведомит вашего лечащего врача в ClinicPanel.
            </p>
        </div>
    </div>
</x-filament-panels::page>
