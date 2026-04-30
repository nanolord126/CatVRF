<x-slot name="title">
    Kitchen Display System
</x-slot>

<div class="min-h-screen bg-gray-100">
    <!-- Header -->
    <div class="bg-white shadow-md">
        <div class="max-w-7xl mx-auto px-4 py-4">
            <div class="flex items-center justify-between">
                <h1 class="text-3xl font-bold text-gray-900">🍳 Kitchen Display System</h1>
                <div class="flex items-center gap-4">
                    @if($autoRefresh)
                        <span class="text-sm text-green-600 font-medium">
                            ● Auto-refresh: {{ $refreshInterval }}s
                        </span>
                    @endif
                    <button wire:click="toggleAutoRefresh" 
                            class="px-4 py-2 rounded-lg {{ $autoRefresh ? 'bg-green-100 text-green-700' : 'bg-gray-200 text-gray-700' }}">
                        {{ $autoRefresh ? 'Pause' : 'Resume' }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 py-6">
        <!-- Stats -->
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-4 mb-6">
            <div class="bg-white rounded-lg shadow p-4">
                <div class="text-2xl font-bold text-gray-900">{{ $stats['total_active'] ?? 0 }}</div>
                <div class="text-sm text-gray-500">Active</div>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <div class="text-2xl font-bold text-blue-600">{{ $stats['pending'] ?? 0 }}</div>
                <div class="text-sm text-gray-500">Pending</div>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <div class="text-2xl font-bold text-yellow-600">{{ $stats['in_progress'] ?? 0 }}</div>
                <div class="text-sm text-gray-500">In Progress</div>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <div class="text-2xl font-bold text-green-600">{{ $stats['ready'] ?? 0 }}</div>
                <div class="text-sm text-gray-500">Ready</div>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <div class="text-2xl font-bold text-orange-600">{{ $stats['problem'] ?? 0 }}</div>
                <div class="text-sm text-gray-500">Problems</div>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <div class="text-2xl font-bold text-red-600">{{ $stats['overdue'] ?? 0 }}</div>
                <div class="text-sm text-gray-500">Overdue</div>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <div class="text-2xl font-bold text-purple-600">{{ $stats['vip_orders'] ?? 0 }}</div>
                <div class="text-sm text-gray-500">VIP</div>
            </div>
        </div>

        <!-- Station Tabs -->
        <div class="bg-white rounded-lg shadow mb-6">
            <div class="flex overflow-x-auto">
                @foreach($stations as $station)
                    <button wire:click="selectStation({{ $station->id }})"
                            class="px-6 py-4 whitespace-nowrap border-b-2 transition-colors
                                   {{ $selectedStationId === $station->id 
                                       ? 'border-blue-500 bg-blue-50' 
                                       : 'border-transparent hover:bg-gray-50' }}">
                        <div class="font-semibold text-lg">{{ $station->name }}</div>
                        <div class="text-sm text-gray-500">{{ $station->type->label() }}</div>
                    </button>
                @endforeach
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow p-4 mb-6">
            <div class="flex gap-4 flex-wrap">
                <select wire:model.live="filterStatus" class="rounded-lg border-gray-300">
                    <option value="all">All Statuses</option>
                    <option value="pending">Pending</option>
                    <option value="in_progress">In Progress</option>
                    <option value="ready">Ready</option>
                    <option value="problem">Problem</option>
                </select>
                <select wire:model.live="filterPriority" class="rounded-lg border-gray-300">
                    <option value="all">All Priorities</option>
                    <option value="normal">Normal</option>
                    <option value="high">High</option>
                    <option value="urgent">Urgent</option>
                    <option value="vip">VIP</option>
                    <option value="emergency">Emergency</option>
                </select>
            </div>
        </div>

        <!-- Orders Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            @foreach($orders as $orderStatus)
                <livewire:order-card :order-status="$orderStatus" :key="$orderStatus->id" />
            @endforeach
        </div>

        @if($orders->isEmpty())
            <div class="text-center py-12 text-gray-500">
                <div class="text-6xl mb-4">🍽️</div>
                <div class="text-xl">No orders for this station</div>
            </div>
        @endif
    </div>
</div>
