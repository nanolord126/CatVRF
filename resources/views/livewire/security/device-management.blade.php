<div>
    <x-slot name="header">
        <h2 class="text-xl font-semibold">Device Management</h2>
    </x-slot>

    <div class="space-y-6">
        <!-- Current Device Info -->
        @if($currentDeviceId)
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h3 class="font-medium text-blue-900">Current Device</h3>
                <p class="text-sm text-blue-700 mt-1">This is the device you're currently using.</p>
            </div>
        @endif

        <!-- Devices List -->
        <div class="space-y-3">
            @foreach($devices as $device)
                <div class="flex items-center justify-between p-4 border rounded-lg {{ $device->is_revoked ? 'opacity-50' : '' }}">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="font-medium">{{ $device->device_name }}</span>
                            @if($device->is_current)
                                <span class="px-2 py-0.5 text-xs bg-blue-100 text-blue-800 rounded">Current</span>
                            @endif
                            @if($device->is_trusted)
                                <span class="px-2 py-0.5 text-xs bg-green-100 text-green-800 rounded">Trusted</span>
                            @endif
                            @if($device->is_revoked)
                                <span class="px-2 py-0.5 text-xs bg-red-100 text-red-800 rounded">Revoked</span>
                            @endif
                        </div>
                        <p class="text-sm text-gray-600 mt-1">
                            {{ $device->platform }} • {{ $device->browser }} • Last seen: {{ $device->last_seen_at?->diffForHumans() }}
                        </p>
                        <p class="text-xs text-gray-500 mt-1">
                            Auth count: {{ $device->auth_count }} • {{ $device->location_country ?? 'Unknown location' }}
                        </p>
                    </div>

                    @if(!$device->is_revoked)
                        <div class="flex gap-2">
                            @if(!$device->is_trusted)
                                <button wire:click="trustDevice('{{ $device->id }}')" class="px-3 py-1 text-sm bg-green-600 text-white rounded hover:bg-green-700">
                                    Trust
                                </button>
                            @endif
                            @if($device->id !== $currentDeviceId)
                                <button wire:click="revokeDevice('{{ $device->id }}')" class="px-3 py-1 text-sm bg-red-600 text-white rounded hover:bg-red-700">
                                    Revoke
                                </button>
                            @endif
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        <!-- Revoke All Others -->
        @if($devices->where('is_revoked', false)->count() > 1)
            <div class="border-t pt-4">
                <button wire:click="revokeAllOthers" class="px-4 py-2 text-sm bg-gray-600 text-white rounded hover:bg-gray-700">
                    Revoke All Other Devices
                </button>
            </div>
        @endif
    </div>

    <script>
        @this.on('device-revoked', () => {
            alert('Device revoked successfully');
        });

        @this.on('device-trusted', () => {
            alert('Device trusted successfully');
        });

        @this.on('all-devices-revoked', () => {
            alert('All other devices revoked successfully');
        });
    </script>
</div>
