<div class="passkey-manager">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-semibold text-gray-900">Your Passkeys</h2>
        <button wire:click="loadCredentials" class="text-sm text-blue-600 hover:text-blue-500">
            Refresh
        </button>
    </div>

    @if($error)
        <div class="alert alert-error mb-4">
            <svg class="w-5 h-5 inline mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
            </svg>
            <span>{{ $error }}</span>
        </div>
    @endif

    <!-- Stats -->
    @if($stats)
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200">
                <div class="text-2xl font-bold text-gray-900">{{ $stats['total'] }}</div>
                <div class="text-sm text-gray-500">Total</div>
            </div>
            <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200">
                <div class="text-2xl font-bold text-blue-600">{{ $stats['platform'] }}</div>
                <div class="text-sm text-gray-500">Platform</div>
            </div>
            <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200">
                <div class="text-2xl font-bold text-green-600">{{ $stats['syncable'] }}</div>
                <div class="text-sm text-gray-500">Synced</div>
            </div>
            <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200">
                <div class="text-sm font-medium text-gray-900 truncate">{{ $this->formatDate($stats['last_used']) }}</div>
                <div class="text-sm text-gray-500">Last Used</div>
            </div>
        </div>
    @endif

    <!-- Loading -->
    @if($loading)
        <div class="text-center py-8">
            <svg class="animate-spin h-8 w-8 text-blue-600 mx-auto" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <p class="mt-2 text-gray-500">Loading passkeys...</p>
        </div>
    @elseif(count($credentials) === 0)
        <div class="text-center py-8 bg-gray-50 rounded-lg">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">No passkeys registered</h3>
            <p class="mt-1 text-sm text-gray-500">Add a passkey to enable passwordless authentication.</p>
        </div>
    @else
        <div class="space-y-3">
            @foreach($credentials as $credential)
                <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200">
                    <div class="flex items-start justify-between">
                        <div class="flex items-start space-x-3">
                            <div class="text-2xl">{{ $this->getDeviceIcon($credential) }}</div>
                            <div>
                                <!-- Edit Mode -->
                                @if($editingCredentialId === $credential['id'])
                                    <div class="flex items-center space-x-2">
                                        <input
                                            wire:model="newName"
                                            type="text"
                                            class="border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                            wire:keydown.enter="renameCredential"
                                        />
                                        <button
                                            wire:click="renameCredential"
                                            :disabled="$loading"
                                            class="text-green-600 hover:text-green-700"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                        </button>
                                        <button
                                            wire:click="cancelEdit"
                                            class="text-gray-600 hover:text-gray-700"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                @else
                                    <!-- View Mode -->
                                    <h3 class="text-sm font-medium text-gray-900">{{ $credential['name'] }}</h3>
                                    <p class="text-xs text-gray-500 mt-1">
                                        {{ $credential['device_type_label'] }}
                                        @if($credential['is_synced'])
                                            <span class="ml-2 text-green-600">• Synced</span>
                                        @endif
                                    </p>
                                    <p class="text-xs text-gray-400 mt-1">
                                        Added: {{ $this->formatDate($credential['created_at']) }}
                                    </p>
                                    <p class="text-xs text-gray-400">
                                        Last used: {{ $this->formatDate($credential['last_used_at']) }}
                                    </p>
                                @endif
                            </div>
                        </div>

                        @if($editingCredentialId !== $credential['id'])
                            <div class="flex space-x-2">
                                <button
                                    wire:click="startEdit({{ $credential['id'] }})"
                                    class="text-gray-400 hover:text-gray-600"
                                    title="Rename"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </button>
                                <button
                                    wire:click="confirmDelete({{ $credential['id'] }})"
                                    class="text-gray-400 hover:text-red-600"
                                    title="Delete"
                                    :disabled="count($credentials) === 1"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <!-- Delete Confirmation Modal -->
    @if($deletingCredentialId)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg p-6 max-w-sm w-full mx-4">
                <h3 class="text-lg font-medium text-gray-900 mb-2">Delete Passkey?</h3>
                <p class="text-sm text-gray-500 mb-4">
                    Are you sure you want to delete "{{ collect($credentials)->firstWhere('id', $deletingCredentialId)['name'] }}"? This action cannot be undone.
                </p>
                @if(count($credentials) === 1)
                    <p class="text-sm text-red-600 mb-4">
                        Warning: This is your last passkey. You will need to use password authentication after deletion.
                    </p>
                @endif
                <div class="flex justify-end space-x-3">
                    <button
                        wire:click="cancelDelete"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200"
                    >
                        Cancel
                    </button>
                    <button
                        wire:click="deleteCredential"
                        :disabled="$loading"
                        class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-md hover:bg-red-700 disabled:opacity-50"
                    >
                        Delete
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>

<style>
.alert {
    display: flex;
    align-items: center;
    padding: 0.75rem;
    border-radius: 0.375rem;
    margin-bottom: 1rem;
}

.alert-error {
    background-color: #fee2e2;
    color: #991b1b;
}
</style>
