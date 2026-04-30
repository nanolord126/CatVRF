<script setup lang="ts">
import { ref } from 'vue';
import { startRegistration } from '@simplewebauthn/browser';
import { useAuthStore } from '@/stores/auth';

interface RegistrationOptions {
  options: {
    challenge: string;
    rp: {
      id: string;
      name: string;
    };
    user: {
      id: string;
      name: string;
      displayName: string;
    };
    pubKeyCredParams: Array<{
      type: string;
      alg: number;
    }>;
    timeout: number;
    attestation: string;
    authenticatorSelection: {
      authenticatorAttachment: string;
      userVerification: string;
      residentKey: string;
    };
    excludeCredentials: Array<{
      id: string;
      type: string;
    }>;
  };
  challenge_id: string;
}

interface Credential {
  id: number;
  name: string;
  device_type: string;
  backed_up: boolean;
  created_at: string;
}

const props = defineProps<{
  authenticatorAttachment?: 'platform' | 'cross-platform';
  userVerificationRequired?: boolean;
  credentialName?: string;
}>();

const emit = defineEmits<{
  success: [credential: Credential];
  error: [error: string];
}>();

const loading = ref(false);
const error = ref('');
const customName = ref(props.credentialName || '');

const authStore = useAuthStore();

async function registerPasskey() {
  loading.value = true;
  error.value = '';

  try {
    const token = authStore.token;
    
    if (!token) {
      throw new Error('You must be logged in to register a passkey');
    }

    // Step 1: Get registration options
    const optionsResponse = await fetch('/api/v1/auth/passkey/register-options', {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        authenticator_attachment: props.authenticatorAttachment || 'platform',
        user_verification_required: props.userVerificationRequired ?? true,
      }),
    });

    if (!optionsResponse.ok) {
      throw new Error('Failed to get registration options');
    }

    const data: RegistrationOptions = await optionsResponse.json();

    // Step 2: Create credential
    const attestation = await startRegistration(data.options);

    // Step 3: Complete registration
    const completeResponse = await fetch('/api/v1/auth/passkey/register', {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        challenge_id: data.challenge_id,
        attestation,
        credential_name: customName.value || undefined,
      }),
    });

    if (!completeResponse.ok) {
      const errorData = await completeResponse.json();
      throw new Error(errorData.error || 'Registration failed');
    }

    const result = await completeResponse.json();

    emit('success', result.credential);
  } catch (err: any) {
    console.error('Passkey registration error:', err);
    
    if (err.name === 'NotAllowedError') {
      error.value = 'Registration was cancelled or timed out';
    } else if (err.name === 'NotSupportedError') {
      error.value = 'Passkeys are not supported on this device';
    } else if (err.name === 'SecurityError') {
      error.value = 'Security error: Please ensure you are using HTTPS';
    } else {
      error.value = err.message || 'Registration failed';
    }
    
    emit('error', error.value);
  } finally {
    loading.value = false;
  }
}
</script>

<template>
  <div class="passkey-register">
    <div v-if="error" class="alert alert-error">
      <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
      </svg>
      <span>{{ error }}</span>
    </div>

    <div class="space-y-4">
      <div v-if="!credentialName">
        <label for="credential-name" class="block text-sm font-medium text-gray-700">
          Passkey Name (optional)
        </label>
        <input
          id="credential-name"
          v-model="customName"
          type="text"
          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
          placeholder="e.g., iPhone Face ID"
        />
        <p class="mt-1 text-xs text-gray-500">
          Give your passkey a recognizable name to help you identify it later.
        </p>
      </div>

      <button
        @click="registerPasskey"
        :disabled="loading"
        class="w-full flex justify-center items-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed"
      >
        <svg v-if="loading" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <svg v-else class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
        </svg>
        <span v-if="loading">Registering...</span>
        <span v-else>Register Passkey</span>
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
  </div>
</template>

<style scoped>
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

.alert svg {
  flex-shrink: 0;
  margin-right: 0.5rem;
}
</style>
