<div>
    <x-slot name="header">
        <h2 class="text-xl font-semibold">Security Dashboard</h2>
    </x-slot>

    <div class="space-y-6">
        <!-- Security Level -->
        <div class="flex items-center justify-between p-4 rounded-lg border-2 {{ 'border-' . $this->securityLevelColor . '-500' }}">
            <div>
                <h3 class="font-medium">Security Level</h3>
                <p class="text-sm text-gray-600 mt-1">
                    Your account security is <strong class="{{ 'text-' . $this->securityLevelColor . '-600' }}">{{ ucfirst($this->securityLevel) }}</strong>
                </p>
            </div>
            <div class="text-3xl">
                @if($securityLevel === 'high')
                    🛡️
                @elseif($securityLevel === 'medium')
                    ⚠️
                @elseif($securityLevel === 'critical')
                    🚨
                @else
                    🔓
                @endif
            </div>
        </div>

        <!-- Security Metrics -->
        <div class="grid grid-cols-2 gap-4">
            <div class="p-4 bg-gray-50 rounded-lg">
                <p class="text-sm text-gray-600">Account Status</p>
                <p class="text-lg font-medium {{ $accountLocked ? 'text-red-600' : 'text-green-600' }}">
                    {{ $accountLocked ? 'Locked' : 'Active' }}
                </p>
            </div>

            <div class="p-4 bg-gray-50 rounded-lg">
                <p class="text-sm text-gray-600">Fraud Score</p>
                <p class="text-lg font-medium {{ $fraudScore < 0.30 ? 'text-green-600' : ($fraudScore < 0.70 ? 'text-yellow-600' : 'text-red-600') }}">
                    {{ number_format($fraudScore * 100, 0) }}%
                </p>
            </div>

            <div class="p-4 bg-gray-50 rounded-lg">
                <p class="text-sm text-gray-600">Passkeys</p>
                <p class="text-lg font-medium">
                    {{ $passkeyCount }}
                </p>
            </div>

            <div class="p-4 bg-gray-50 rounded-lg">
                <p class="text-sm text-gray-600">Devices</p>
                <p class="text-lg font-medium">
                    {{ $deviceCount }}
                </p>
            </div>

            <div class="p-4 bg-gray-50 rounded-lg">
                <p class="text-sm text-gray-600">Face Verified</p>
                <p class="text-lg font-medium {{ $faceVerified ? 'text-green-600' : 'text-gray-600' }}">
                    {{ $faceVerified ? 'Yes' : 'No' }}
                </p>
            </div>
        </div>

        <!-- Recommendations -->
        <div class="p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
            <h3 class="font-medium text-yellow-900">Security Recommendations</h3>
            <ul class="mt-2 space-y-1 text-sm text-yellow-800">
                @if($passkeyCount < 2)
                    <li>• Add at least 2 passkeys for better security</li>
                @endif
                @if(!$faceVerified)
                    <li>• Enable face verification for additional security</li>
                @endif
                @if($deviceCount > 5)
                    <li>• Review and revoke unused devices</li>
                @endif
                @if($fraudScore > 0.30)
                    <li>• Your account has some risk indicators. Contact support if needed.</li>
                @endif
            </ul>
        </div>

        <!-- Quick Actions -->
        <div class="flex gap-2">
            <a href="{{ route('devices.index') }}" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                Manage Devices
            </a>
            <a href="{{ route('security.recovery') }}" class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700">
                Account Recovery
            </a>
        </div>
    </div>
</div>
