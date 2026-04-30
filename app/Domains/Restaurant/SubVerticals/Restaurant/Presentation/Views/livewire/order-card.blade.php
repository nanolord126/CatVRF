<div class="bg-white rounded-lg shadow-lg overflow-hidden {{ $isOverdue ? 'ring-2 ring-red-500' : '' }}"
     x-data="{ showDetails: {{ $showDetails ? 'true' : 'false' }} }">
    <!-- Priority Badge -->
    @if($status->priority->value !== 'normal')
        <div class="bg-{{ $status->priority->color() }}-500 text-white px-4 py-1 text-center font-bold text-sm">
            {{ strtoupper($status->priority->label()) }}
        </div>
    @endif

    <!-- VIP Badge -->
    @if($status->isVip)
        <div class="bg-purple-600 text-white px-4 py-1 text-center font-bold text-sm flex items-center justify-center gap-2">
            👑 VIP GUEST
        </div>
    @endif

    <!-- Marketplace Badge -->
    @if($status->isFromMarketplace)
        <div class="bg-blue-600 text-white px-4 py-1 text-center font-bold text-sm flex items-center justify-center gap-2">
            🛒 MARKETPLACE
        </div>
    @endif

    <!-- Header -->
    <div class="p-4 border-b">
        <div class="flex items-center justify-between mb-2">
            <span class="text-2xl font-bold">#{{ $order->id ?? $status->orderId }}</span>
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-sm font-medium
                {{ match($status->status->value) {
                    'paid', 'completed' => 'bg-emerald-100 text-emerald-800',
                    'partially_paid' => 'bg-amber-100 text-amber-800',
                    'pending_payment' => 'bg-yellow-100 text-yellow-800',
                    'in_kitchen' => 'bg-blue-100 text-blue-800',
                    'ready' => 'bg-emerald-100 text-emerald-800',
                    'cancelled' => 'bg-red-100 text-red-800',
                    'draft' => 'bg-gray-100 text-gray-800',
                    default => 'bg-gray-100 text-gray-800',
                } }}">
                @if($status->status->icon())
                    <x-dynamic-component :component="'heroicon-o-' . str_replace('heroicon-o-', '', $status->status->icon())" class="w-4 h-4" />
                @endif
                {{ $status->status->label() }}
            </span>
        </div>
        
        <!-- Timer -->
        <div class="{{ $this->getTimerColor() }} rounded-lg p-3 text-center">
            @if($status->status->value === 'pending')
                <div class="text-sm">Est. Time: {{ $status->estimatedPreparationTime->minutes }} min</div>
            @else
                <div class="text-3xl font-bold">
                    @if($isOverdue)
                        +{{ $elapsedMinutes - $status->estimatedPreparationTime->minutes }}
                    @else
                        {{ $timeRemaining }}
                    @endif
                    min
                </div>
                <div class="text-sm">
                    @if($isOverdue)
                        OVERDUE
                    @else
                        remaining
                    @endif
                </div>
            @endif
        </div>

        <!-- Progress Bar -->
        @if($status->status->value !== 'pending')
            <div class="mt-3">
                <div class="w-full bg-gray-200 rounded-full h-3">
                    <div class="h-3 rounded-full transition-all duration-300 
                                {{ $isOverdue ? 'bg-red-500' : 'bg-green-500' }}"
                         style="width: {{ $progress }}%"></div>
                </div>
            </div>
        @endif
    </div>

    <!-- Order Items -->
    <div class="p-4">
        @if($order && $order->items)
            @foreach($order->items as $item)
                <div class="flex justify-between items-center py-2 border-b last:border-0">
                    <div>
                        <span class="font-semibold text-lg">{{ $item->quantity }}x</span>
                        <span class="ml-2">{{ $item->product_name }}</span>
                    </div>
                </div>
            @endforeach
        @else
            <div class="text-gray-500">Order #{{ $status->orderId }}</div>
        @endif
    </div>

    <!-- Actions -->
    <div class="p-4 bg-gray-50 border-t">
        <div class="grid grid-cols-2 gap-2">
            @if($status->status->value === 'pending')
                <button wire:click="startOrder" 
                        class="col-span-2 bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-lg text-lg transition-colors">
                    ▶ START
                </button>
            @endif

            @if($status->status->value === 'in_progress')
                <button wire:click="completeOrder" 
                        class="col-span-2 bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-4 rounded-lg text-lg transition-colors">
                    ✓ READY
                </button>
            @endif

            @if($status->status->value === 'ready')
                <button wire:click="serveOrder" 
                        class="col-span-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3 px-4 rounded-lg text-lg transition-colors">
                    📤 SERVE
                </button>
            @endif

            <button @click="showDetails = !showDetails"
                    class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold py-2 px-4 rounded-lg transition-colors">
                {{ $showDetails ? '▲' : '▼' }} Details
            </button>

            <button wire:click="cancelOrder" 
                    class="bg-red-100 hover:bg-red-200 text-red-700 font-bold py-2 px-4 rounded-lg transition-colors">
                ✕ Cancel
            </button>
        </div>
    </div>

    <!-- Details Modal -->
    <div x-show="showDetails" x-transition class="p-4 bg-white border-t">
        <!-- Priority Selection -->
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">Priority</label>
            <div class="flex gap-2 flex-wrap">
                @foreach(['normal', 'high', 'urgent', 'vip', 'emergency'] as $priority)
                    <button wire:click="setPriority('{{ $priority }}')"
                            class="px-3 py-1 rounded text-sm font-medium
                                   {{ $status->priority->value === $priority 
                                       ? 'bg-' . \Modules\Restaurant\Domain\Enums\OrderPriority::from($priority)->color() . '-500 text-white' 
                                       : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
                        {{ ucfirst($priority) }}
                    </button>
                @endforeach
            </div>
        </div>

        <!-- Problem Report -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Report Problem</label>
            <textarea wire:model="problemComment" 
                      class="w-full rounded-lg border-gray-300 shadow-sm"
                      rows="3"
                      placeholder="Describe the issue..."></textarea>
            <button wire:click="submitProblem"
                    class="mt-2 bg-orange-600 hover:bg-orange-700 text-white font-bold py-2 px-4 rounded-lg">
                Report Problem
            </button>
        </div>
    </div>
</div>
