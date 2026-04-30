<div class="passkey-register">
    @if($error)
        <div class="alert alert-error mb-4">
            <svg class="w-5 h-5 inline mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
            </svg>
            <span>{{ $error }}</span>
        </div>
    @endif

    <div class="space-y-4">
        <div>
            <label for="credential-name" class="block text-sm font-medium text-gray-700">
                Passkey Name (optional)
            </label>
            <input
                id="credential-name"
                wire:model="credentialName"
                type="text"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                placeholder="e.g., iPhone Face ID"
            />
            <p class="mt-1 text-xs text-gray-500">
                Give your passkey a recognizable name to help you identify it later.
            </p>
        </div>

        <button
            wire:click="initiateRegistration"
            :disabled="$loading"
            class="w-full flex justify-center items-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed"
        >
            <svg x-show="$loading" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <svg x-show="!$loading" class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
            <span x-text="$loading ? 'Registering...' : 'Register Passkey'"></span>
        </button>

        <div class="text-xs text-gray-500">
            <p class="font-medium">What happens next:</p>
            <ul class="mt-1 list-disc list-inside space-y-1">
                <li>Your browser will prompt for biometric authentication</li>
                <li>Use Face ID, Touch ID, or Windows Hello to verify</li>
                <li>Your passkey will be stored securely on this device</li>
                <li>You can use it to sign in without a password</li>
            </ul>
        </div>
    </div>

    @script
        window.addEventListener('passkey-register-initiate', async (event) => {
            try {
                const attestation = await SimpleWebAuthnBrowser.startRegistration(event.detail.options);
                @this.completeRegistration(attestation, event.detail.challenge_id);
            } catch (err) {
                console.error('WebAuthn error:', err);
                @this.setErrorMessage(err.message || 'Registration failed');
            }
        });
        
        window.SimpleWebAuthnBrowser = {
            startRegistration: async (options) => {
                // This would be replaced by actual @simplewebauthn/browser
                // For now, placeholder
                return {};
            }
        };
    @endscript
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
