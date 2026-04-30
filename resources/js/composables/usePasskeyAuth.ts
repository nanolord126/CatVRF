import { ref } from 'vue';
import { startRegistration, startAuthentication } from '@simplewebauthn/browser';

interface RegistrationOptions {
  options: any;
  challenge_id: string;
}

interface AuthenticationOptions {
  options: any;
  challenge_id: string;
  user_found: boolean;
  has_credentials: boolean;
}

export function usePasskeyAuth() {
  const loading = ref(false);
  const error = ref('');

  /**
   * Check if WebAuthn is supported
   */
  async function isSupported(): Promise<boolean> {
    return !!(
      window.PublicKeyCredential &&
      PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable &&
      await PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable()
    );
  }

  /**
   * Check if conditional UI (autofill) is available
   */
  async function isConditionalMediationAvailable(): Promise<boolean> {
    if (window.PublicKeyCredential && 
        PublicKeyCredential.isConditionalMediationAvailable) {
      return await PublicKeyCredential.isConditionalMediationAvailable();
    }
    return false;
  }

  /**
   * Register a new passkey
   */
  async function registerPasskey(params: {
    token: string;
    authenticatorAttachment?: 'platform' | 'cross-platform';
    userVerificationRequired?: boolean;
    credentialName?: string;
  }) {
    loading.value = true;
    error.value = '';

    try {
      // Step 1: Get registration options
      const optionsResponse = await fetch('/api/v1/auth/passkey/register-options', {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${params.token}`,
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          authenticator_attachment: params.authenticatorAttachment !== undefined ? params.authenticatorAttachment : 'platform',
          user_verification_required: params.userVerificationRequired !== undefined ? params.userVerificationRequired : true,
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
          'Authorization': `Bearer ${params.token}`,
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          challenge_id: data.challenge_id,
          attestation,
          credential_name: params.credentialName,
        }),
      });

      if (!completeResponse.ok) {
        const errorData = await completeResponse.json();
        throw new Error(errorData.error !== undefined ? errorData.error : 'Registration failed');
      }

      return await completeResponse.json();
    } catch (err: any) {
      console.error('Passkey registration error:', err);
      
      if (err.name === 'NotAllowedError') {
        error.value = 'Registration was cancelled or timed out';
      } else if (err.name === 'NotSupportedError') {
        error.value = 'Passkeys are not supported on this device';
      } else if (err.name === 'SecurityError') {
        error.value = 'Security error: Please ensure you are using HTTPS';
      } else {
        error.value = err.message !== undefined ? err.message : 'Registration failed';
      }
      
      throw err;
    } finally {
      loading.value = false;
    }
  }

  /**
   * Authenticate with passkey
   */
  async function authenticateWithPasskey(params: {
    email: string;
  }) {
    loading.value = true;
    error.value = '';

    try {
      // Step 1: Get authentication options
      const optionsResponse = await fetch('/api/v1/auth/passkey/login-options', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email: params.email }),
      });

      if (!optionsResponse.ok) {
        throw new Error('Failed to get login options');
      }

      const data: AuthenticationOptions = await optionsResponse.json();

      if (!data.has_credentials) {
        error.value = 'No passkeys registered for this account';
        return null;
      }

      // Step 2: Start authentication
      const assertion = await startAuthentication(data.options);

      // Step 3: Complete authentication
      const completeResponse = await fetch('/api/v1/auth/passkey/login', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          challenge_id: data.challenge_id,
          assertion,
        }),
      });

      if (!completeResponse.ok) {
        throw new Error('Authentication failed');
      }

      return await completeResponse.json();
    } catch (err: any) {
      console.error('Passkey authentication error:', err);
      
      if (err.name === 'NotAllowedError') {
        error.value = 'Authentication was cancelled or timed out';
      } else if (err.name === 'NotSupportedError') {
        error.value = 'Passkeys are not supported on this device';
      } else {
        error.value = err.message !== undefined ? err.message : 'Authentication failed';
      }
      
      throw err;
    } finally {
      loading.value = false;
    }
  }

  /**
   * List user credentials
   */
  async function listCredentials(token: string) {
    const response = await fetch('/api/v1/auth/passkey/credentials', {
      headers: {
        'Authorization': `Bearer ${token}`,
      },
    });

    if (!response.ok) {
      throw new Error('Failed to load credentials');
    }

    return await response.json();
  }

  /**
   * Delete a credential
   */
  async function deleteCredential(params: {
    token: string;
    credentialId: number;
    revokeSessions?: boolean;
  }) {
    const response = await fetch(`/api/v1/auth/passkey/credentials/${params.credentialId}`, {
      method: 'DELETE',
      headers: {
        'Authorization': `Bearer ${params.token}`,
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ revoke_sessions: params.revokeSessions !== undefined ? params.revokeSessions : true }),
    });

    if (!response.ok) {
      const errorData = await response.json();
      throw new Error(errorData.error !== undefined ? errorData.error : 'Failed to delete credential');
    }

    return await response.json();
  }

  /**
   * Rename a credential
   */
  async function renameCredential(params: {
    token: string;
    credentialId: number;
    name: string;
  }) {
    const response = await fetch(`/api/v1/auth/passkey/credentials/${params.credentialId}`, {
      method: 'PUT',
      headers: {
        'Authorization': `Bearer ${params.token}`,
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ name: params.name }),
    });

    if (!response.ok) {
      throw new Error('Failed to rename credential');
    }

    return await response.json();
  }

  return {
    loading,
    error,
    isSupported,
    isConditionalMediationAvailable,
    registerPasskey,
    authenticateWithPasskey,
    listCredentials,
    deleteCredential,
    renameCredential,
  };
}
