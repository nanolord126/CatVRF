<x-filament-panels::page>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        
        <!-- Chat & Command Interface -->
        <x-filament::section>
            <x-slot name="heading">Voice & Chat Assistant CLI 2026</x-slot>
            <x-slot name="description">Speak or type naturally to manage the multi-tenant vertical ecosystem.</x-slot>

            <div class="space-y-4 max-h-[400px] overflow-y-auto mb-6 p-4 border rounded-lg dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
                @foreach($chatLog as $entry)
                    <div class="flex {{ $entry['role'] === 'user' ? 'justify-end' : 'justify-start' }}">
                        <div class="max-w-[80%] p-3 rounded-lg {{ $entry['role'] === 'user' ? 'bg-primary-600 text-white' : 'bg-gray-200 dark:bg-gray-700 text-black dark:text-white' }}">
                            <p class="text-xs opacity-50 mb-1 {{ $entry['role'] === 'user' ? 'text-right' : 'text-left' }}">{{ strtoupper($entry['role']) }}</p>
                            <p class="text-sm italic">"{{ $entry['content'] }}"</p>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="flex gap-2">
                <div class="flex-grow">
                     {{ $this->form }}
                </div>
                <!-- Mocked Voice Button -->
                <button 
                    wire:click="toggleListening"
                    class="p-4 rounded-full transition-all duration-300 {{ $isListening ? 'bg-red-500 animate-pulse text-white' : 'bg-primary-600 text-white hover:bg-primary-700' }}"
                >
                    <x-heroicon-o-microphone class="w-6 h-6" />
                </button>
            </div>
            
            @if($isListening)
                <div class="mt-4 text-center text-xs animate-bounce text-primary-500">
                    Listening for Ecosystem Voice Command... (2026 AI Ready)
                </div>
            @endif
        </x-filament::section>

        <!-- Command Documentation / Context -->
        <x-filament::section>
            <x-slot name="heading">Available 2026 Commands</x-slot>
            <x-slot name="description">Quick reference for the NLP engine mapping.</x-slot>

            <div class="space-y-4 text-sm">
                <div class="p-4 border-l-4 border-primary-500 bg-gray-100 dark:bg-gray-800">
                    <h4 class="font-bold">📊 Reporting & Analytics</h4>
                    <ul class="list-disc list-inside mt-2 opacity-75">
                        <li>"How is taxi revenue today?"</li>
                        <li>"Show sales summary for food"</li>
                        <li>"Check status for clinics"</li>
                    </ul>
                </div>
                <div class="p-4 border-l-4 border-green-500 bg-gray-100 dark:bg-gray-800">
                    <h4 class="font-bold">🚢 Logistics & Supply</h4>
                    <ul class="list-disc list-inside mt-2 opacity-75">
                        <li>"Analyze logistics stock"</li>
                        <li>"Go to logistics page"</li>
                    </ul>
                </div>
                <div class="p-4 border-l-4 border-yellow-500 bg-gray-100 dark:bg-gray-800">
                    <h4 class="font-bold">🧪 Simulations & Fraud</h4>
                    <ul class="list-disc list-inside mt-2 opacity-75">
                        <li>"Run digital twin scenario"</li>
                        <li>"Show suspicious activities"</li>
                        <li>"Show security alerts"</li>
                    </ul>
                </div>
            </div>
        </x-filament::section>

    </div>
</x-filament-panels::page>
