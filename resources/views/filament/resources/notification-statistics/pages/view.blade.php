<x-filament-panels::page>
    <x-slot name="header">
        <x-filament-panels::heading>
            Notification Statistics
        </x-filament-panels::heading>
    </x-slot>

    <div class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            {{ $this->getHeaderWidgets()[0] }}
        </div>
        
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{ $this->getHeaderWidgets()[1] }}
            {{ $this->getHeaderWidgets()[2] }}
        </div>
        
        <div>
            {{ $this->getHeaderWidgets()[3] }}
        </div>
    </div>
</x-filament-panels::page>
