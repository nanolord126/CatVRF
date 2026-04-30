<div class="passkey-login" x-data="{ 
    isSupported: false,
    conditionalUI: false,
    assertion: null,
    challengeId: null
}" x-init="checkSupport()">
    @script
        function checkSupport() {
            this.isSupported = !!(
                window.PublicKeyCredential &&
                PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable &&
                await PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable()
            );
            
            if (window.PublicKeyCredential && 
                PublicKeyCredential.isConditionalMediationAvailable) {
                this.conditionalUI = await PublicKeyCredential.isConditionalMediationAvailable();
            }
        }
        
        async function handleWebAuthnAssertion(options, challenge_id) {
            try {
                this.assertion = await startAuthentication(options);
                this.challengeId = challenge_id;
                
                @this.completeLogin(this.assertion, this.challengeId);
            } catch (err) {
                console.error('WebAuthn error:', err);
                @this.setErrorMessage(err.message || 'Authentication failed');
            }
        }
        
        window.startAuthentication = async (options) => {
            return await SimpleWebAuthnBrowser.startAuthentication(options);
        };
        
        window.addEventListener('passkey-login-initiate', (event) => {
            handleWebAuthnAssertion(event.detail.options, event.detail.challenge_id);
        });
    @endscript

    <div x-show="!isSupported" class="alert alert-warning mb-4">
        <svg class="w-5 h-5 inline mr-2" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
        </svg>
        <span>Passkeys are not supported on this device. Please use password authentication.</span>
    </div>

    <form wire:submit.prevent="initiateLogin" class="space-y-4">
        <div>
            <label for="email" class="block text-sm font-medium text-gray-700">
                Email
            </label>
            <input
                id="email"
                wire:model="email"
                type="email"
                required
                autocomplete="username webauthn"
                x-bind:autocomplete="conditionalUI ? 'webauthn' : 'username'"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('email') border-red-500 @enderror"
                placeholder="user@example.com"
            />
            @error('email')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        @if($error)
            <div class="alert alert-error">
                <svg class="w-5 h-5 inline mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                </svg>
                <span>{{ $error }}</span>
            </div>
        @endif

        <button
            type="submit"
            :disabled="$loading || !isSupported"
            class="w-full flex justify-center items-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
        >
            <svg x-show="$loading" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <svg x-show="!$loading" class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.131A8 8 0 008 4.07M3 15.364c.64-1.319 1-2.8 1-4.364 0-1.457.39-2.823 1.07-4" />
            </svg>
            <span x-text="$loading ? 'Authenticating...' : 'Sign in with Passkey'"></span>
        </button>
    </form>

    <div class="mt-4 text-center">
        <a href="/login" class="text-sm text-blue-600 hover:text-blue-500">
            Use password instead
        </a>
    </div>
</div>

<style>
.alert {
    display: flex;
    align-items: center;
    padding: 0.75rem;
    border-radius: 0.375rem;
    margin-bottom: 1rem;
}

.alert-warning {
    background-color: #fef3c7;
    color: #92400e;
}

.alert-error {
    background-color: #fee2e2;
    color: #991b1b;
}
</style>
