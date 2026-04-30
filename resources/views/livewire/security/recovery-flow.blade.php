<div>
    <x-slot name="header">
        <h2 class="text-xl font-semibold">Account Recovery</h2>
    </x-slot>

    <!-- Step 1: Init -->
    @if($status === 'init')
        <div class="space-y-4">
            <p class="text-gray-600">Enter your email to start the account recovery process.</p>
            
            <form wire:submit.prevent="initRecovery">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Email</label>
                        <input type="email" wire:model="email" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                        @error('email') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Recovery Method</label>
                        <select wire:model="method" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            <option value="email">Email (OTP)</option>
                            <option value="sms">SMS (OTP)</option>
                            <option value="backup_code">Backup Code</option>
                            <option value="ai_face">AI Face Verification</option>
                        </select>
                    </div>

                    <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                        Start Recovery
                    </button>
                </div>
            </form>
        </div>
    @endif

    <!-- Step 2: Verification -->
    @if($status === 'verification')
        <div class="space-y-4">
            <p class="text-gray-600">
                We've sent a verification code to your {{ $method === 'email' ? 'email' : ($method === 'sms' ? 'phone' : 'backup codes') }}.
                Enter the code below to continue.
            </p>
            
            <form wire:submit.prevent="verifyRecovery">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Verification Code</label>
                        <input type="text" wire:model="verificationCode" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                        @error('verificationCode') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                        Verify
                    </button>

                    <button type="button" wire:click="reset" class="w-full px-4 py-2 text-gray-600 hover:text-gray-800">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    @endif

    <!-- Step 3: Complete (Register New Passkey) -->
    @if($status === 'complete')
        <div class="space-y-4">
            <div class="p-4 bg-green-50 border border-green-200 rounded-lg">
                <p class="text-green-800 font-medium">Verification Successful!</p>
                <p class="text-green-700 text-sm mt-1">You can now register a new passkey to complete the recovery.</p>
            </div>

            <p class="text-gray-600">
                Please register a new passkey to secure your account. All old passkeys will be revoked.
            </p>

            <!-- This would integrate with the existing PasskeyRegistration component -->
            <a href="{{ route('passkey.register') }}" class="block w-full px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 text-center">
                Register New Passkey
            </a>
        </div>
    @endif

    <!-- Error State -->
    @if($status === 'error')
        <div class="space-y-4">
            <div class="p-4 bg-red-50 border border-red-200 rounded-lg">
                <p class="text-red-800 font-medium">Recovery Failed</p>
                <p class="text-red-700 text-sm mt-1">{{ $errorMessage }}</p>
            </div>

            <button wire:click="reset" class="w-full px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700">
                Try Again
            </button>
        </div>
    @endif
</div>
