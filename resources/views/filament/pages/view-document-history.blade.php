<x-filament-panels::page>
    <x-filament-panels::header>
        <x-filament-panels::heading>
            Document History: {{ $record->document_number }}
        </x-filament-panels::heading>
    </x-filament-panels::header>

    <x-filament-panels::content>
        <x-slot name="heading">
            <h3 class="text-lg font-semibold">Document Details</h3>
        </x-slot>

        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <span class="text-gray-500">Document Number:</span>
                    <p class="font-semibold">{{ $record->document_number }}</p>
                </div>
                <div>
                    <span class="text-gray-500">Type:</span>
                    <p class="font-semibold">{{ $record->document_type }}</p>
                </div>
                <div>
                    <span class="text-gray-500">Status:</span>
                    <p class="font-semibold">{{ $record->status }}</p>
                </div>
                <div>
                    <span class="text-gray-500">Supplier:</span>
                    <p class="font-semibold">{{ $record->seller->name ?? '-' }}</p>
                </div>
                @if($record->batch_number)
                <div>
                    <span class="text-gray-500">Batch Number:</span>
                    <p class="font-semibold">{{ $record->batch_number }}</p>
                </div>
                @endif
                @if($record->supplier_tier_id)
                <div>
                    <span class="text-gray-500">Supplier Tier:</span>
                    <p class="font-semibold">{{ $record->supplierTier->name ?? '-' }}</p>
                </div>
                @endif
            </div>
        </div>

        <x-slot name="heading">
            <h3 class="text-lg font-semibold">Change History</h3>
        </x-slot>

        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Summary</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($history as $entry)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full
                                @if($entry->action === 'created') bg-green-100 text-green-800
                                @elseif($entry->action === 'updated') bg-blue-100 text-blue-800
                                @elseif($entry->action === 'deleted') bg-red-100 text-red-800
                                @elseif($entry->action === 'distributed') bg-purple-100 text-purple-800
                                @elseif($entry->action === 'validated') bg-yellow-100 text-yellow-800
                                @elseif($entry->action === 'expired') bg-gray-100 text-gray-800
                                @elseif($entry->action === 'closed') bg-orange-100 text-orange-800
                                @else bg-gray-100 text-gray-800
                                @endif">
                                {{ $entry->action }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $entry->user_type ?? '-' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $entry->created_at->format('Y-m-d H:i:s') }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            {{ $entry->changes_summary ?? '-' }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-6 py-4 text-center text-gray-500">
                            No history records found
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament-panels::content>
</x-filament-panels::page>
