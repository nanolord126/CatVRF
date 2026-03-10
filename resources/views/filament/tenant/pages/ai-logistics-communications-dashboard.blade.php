<x-filament-panels::page>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <!-- AI Supply KPI -->
        <x-filament::section>
            <x-slot name="heading">AI Predictive Stock Redistribution (Proposals Stream)</x-slot>
            <x-slot name="description">Monitor local demand surges and suggested inventory transfers to prevent OOS (Out-of-Stock) at key locations.</x-slot>
            
            {{ $this->table }}
        </x-filament::section>

        <!-- Smart Communications (AI Personalized Outreach) -->
        <x-filament::section>
            <x-slot name="heading">Digital Outreach: Smart Notification Queue (Next 24 Hours)</x-slot>
            <x-slot name="description">Messages waiting for their AI-calculated "Perfect Moment" based on user behavior patterns.</x-slot>

            <div class="space-y-4">
                @foreach($this->getViewData()['notifications_queue'] as $notification)
                    <div class="p-4 border rounded-lg dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
                        <div class="flex justify-between items-center mb-2">
                            <span class="font-bold text-lg text-primary-600 truncate max-w-[70%]">{{ $notification->title }}</span>
                            <span class="badge {{ $notification->urgency_score > 0.8 ? 'badge-danger' : 'badge-info' }}">
                                {{ $notification->urgency_score > 0.8 ? 'Emergency' : 'Routine' }}
                            </span>
                        </div>
                        <div class="text-xs opacity-70 mb-2">Ctx: {{ $notification->trigger_context }} | Scheduled: {{ $notification->scheduled_send_at }}</div>
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
