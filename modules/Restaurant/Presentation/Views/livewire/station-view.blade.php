<x-slot name="title">
    {{ $station?->name ?? 'Station View' }}
</x-slot>

<div class="min-h-screen bg-gray-100">
    <!-- Header -->
    <div class="bg-{{ $station?->type->color() ?? 'gray' }}-600 text-white shadow-md">
        <div class="max-w-7xl mx-auto px-4 py-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-4xl font-bold">{{ $station?->name ?? 'Station' }}</h1>
                    <p class="text-{{ $station?->type->color() ?? 'gray' }}-100 mt-1">
                        {{ $station?->type->label() ?? '' }}
                    </p>
                </div>
                <div class="text-right">
                    <div class="text-5xl font-bold">{{ $stats['total'] ?? 0 }}</div>
                    <div class="text-sm">Active Orders</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Bar -->
    <div class="bg-white shadow-md">
        <div class="max-w-7xl mx-auto px-4 py-4">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="text-center">
                    <div class="text-3xl font-bold text-blue-600">{{ $stats['pending'] ?? 0 }}</div>
                    <div class="text-sm text-gray-500">Pending</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-bold text-yellow-600">{{ $stats['in_progress'] ?? 0 }}</div>
                    <div class="text-sm text-gray-500">In Progress</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-bold text-green-600">{{ $stats['ready'] ?? 0 }}</div>
                    <div class="text-sm text-gray-500">Ready</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-bold text-red-600">{{ $stats['overdue'] ?? 0 }}</div>
                    <div class="text-sm text-gray-500">Overdue</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Orders Grid -->
    <div class="max-w-7xl mx-auto px-4 py-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            @foreach($orders as $orderStatus)
                <livewire:order-card :order-status="$orderStatus" :key="$orderStatus->id" />
            @endforeach
        </div>

        @if($orders->isEmpty())
            <div class="text-center py-12 text-gray-500">
                <div class="text-6xl mb-4">✨</div>
                <div class="text-xl">All caught up! No orders for this station.</div>
            </div>
        @endif
    </div>
</div>
