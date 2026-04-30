# Passkey Frontend Setup Guide

This guide covers the frontend setup for Passkey authentication in CatVRF.

## Installation

### 1. Install @simplewebauthn/browser

```bash
npm install @simplewebauthn/browser
# or
yarn add @simplewebauthn/browser
# or
pnpm add @simplewebauthn/browser
```

### 2. Update package.json

Add the dependency to your `package.json`:

```json
{
  "dependencies": {
    "@simplewebauthn/browser": "^9.0.3"
  }
}
```

## Vue 3 + TypeScript Usage

### 1. Import Components

```vue
<script setup lang="ts">
import PasskeyLogin from '@/Components/Auth/PasskeyLogin.vue';
import PasskeyRegister from '@/Components/Auth/PasskeyRegister.vue';
import PasskeyManager from '@/Components/Auth/PasskeyManager.vue';
import { usePasskeyAuth } from '@/composables/usePasskeyAuth';
</script>
```

### 2. Use in Login Page

```vue
<template>
  <div>
    <PasskeyLogin 
      @success="handleLoginSuccess" 
      @error="handleLoginError"
    />
    
    <a href="/login-password">Use password instead</a>
  </div>
</template>

<script setup lang="ts">
function handleLoginSuccess(token: string, user: any) {
  // Store token, redirect, etc.
  localStorage.setItem('token', token);
  router.push('/dashboard');
}

function handleLoginError(error: string) {
  console.error('Login failed:', error);
}
</script>
```

### 3. Use in Registration Page

```vue
<template>
  <div>
    <PasskeyRegister 
      @success="handleRegistrationSuccess"
      @error="handleRegistrationError"
    />
  </div>
</template>
```

### 4. Use in Settings Page

```vue
<template>
  <div>
    <PasskeyManager @refresh="loadCredentials" />
  </div>
</template>
```

## Livewire + Blade Usage

### 1. Include Components in Blade

```blade
<!-- Login Page -->
<livewire:auth.passkey-login :redirect="null" />

<a href="/login-password">Use password instead</a>

<!-- Registration Page (user must be authenticated) -->
<livewire:auth.passkey-register />

<!-- Settings Page -->
<livewire:auth.passkey-manager />
```

### 2. Add Alpine.js and @simplewebauthn/browser

In your layout file (e.g., `resources/views/layouts/app.blade.php`):

```blade
<!DOCTYPE html>
<html>
<head>
    <!-- ... -->
    <script src="https://unpkg.com/@simplewebauthn/browser/dist/bundle.umd.min.js"></script>
</head>
<body>
    @livewireScripts
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</body>
</html>
```

Or install via npm:

```bash
npm install @simplewebauthn/browser alpinejs
```

Then import in your main JavaScript file:

```javascript
import Alpine from 'alpinejs';
import * as SimpleWebAuthnBrowser from '@simplewebauthn/browser';

window.Alpine = Alpine;
window.SimpleWebAuthnBrowser = SimpleWebAuthnBrowser;

Alpine.start();
```

## Conditional UI (Autofill)

### Vue 3

The `PasskeyLogin` component automatically supports conditional UI when available.

### Livewire

The Blade template includes conditional UI support via Alpine.js:

```blade
<input
    id="email"
    wire:model="email"
    type="email"
    autocomplete="username webauthn"
    x-bind:autocomplete="conditionalUI ? 'webauthn' : 'username'"
/>
```

## Error Handling

### Common Errors

1. **NotAllowedError**: User cancelled or timed out
2. **NotSupportedError**: Passkeys not supported on device
3. **SecurityError**: Not using HTTPS (required for WebAuthn)
4. **InvalidStateError**: Credential already registered

### Handling Errors in Vue

```vue
<script setup lang="ts">
import { usePasskeyAuth } from '@/composables/usePasskeyAuth';

const { error, registerPasskey } = usePasskeyAuth();

async function handleRegister() {
  try {
    await registerPasskey({
      token: authStore.token,
    });
  } catch (err) {
    if (err.name === 'NotAllowedError') {
      // User cancelled
    } else if (err.name === 'NotSupportedError') {
      // Not supported
    }
  }
}
</script>
```

## Browser Support

Passkeys are supported on:

- **Chrome 67+** (Windows, macOS, Android, Linux)
- **Safari 13+** (macOS, iOS)
- **Firefox 60+** (Windows, macOS, Linux)
- **Edge 18+** (Windows, macOS)

### Device Support

- **iOS 16+**: Face ID, Touch ID
- **macOS Ventura+**: Touch ID
- **Windows 10/11**: Windows Hello
- **Android 9+**: Fingerprint, Face Unlock

## Testing

### Local Development

For local development, you need HTTPS. Use:

```bash
# Laravel Valet with HTTPS
valet secure catvrf.test

# Or use ngrok
ngrok http 8000
```

### Testing with Real Devices

1. Deploy to staging environment with HTTPS
2. Access from real device (iPhone, Android, etc.)
3. Test registration and authentication flows

## Troubleshooting

### "Passkeys are not supported"

**Cause**: Browser or device doesn't support WebAuthn.

**Solution**: 
- Check browser version
- Verify device has biometric capabilities
- Provide password fallback

### "Security error: Please ensure you are using HTTPS"

**Cause**: WebAuthn requires secure context (HTTPS).

**Solution**:
- Use HTTPS in production
- Use localhost for local development
- Use ngrok or similar for testing

### "Authentication was cancelled or timed out"

**Cause**: User cancelled biometric prompt or timeout.

**Solution**:
- Increase timeout in config
- Show user-friendly error message
- Allow retry

### "No passkeys registered for this account"

**Cause**: User hasn't registered any passkeys.

**Solution**:
- Redirect to registration page
- Show helpful message

## Performance Optimization

### Lazy Load Components

```vue
<script setup lang="ts">
const PasskeyLogin = defineAsyncComponent(() => 
  import('@/Components/Auth/PasskeyLogin.vue')
);
</script>
```

### Cache Credentials

```typescript
const { listCredentials } = usePasskeyAuth();

// Cache credentials for 5 minutes
const cachedCredentials = await cache.remember('passkeys:123', 300, async () => {
  return await listCredentials(token);
});
```

## Accessibility

### ARIA Labels

Components include proper ARIA labels:

```vue
<button
  aria-label="Sign in with passkey"
  type="submit"
>
  Sign in with Passkey
</button>
```

### Keyboard Navigation

All components are keyboard accessible:
- Tab to focus
- Enter/Space to activate
- Esc to close modals

## Security Best Practices

1. **Always use HTTPS in production**
2. **Validate all user inputs**
3. **Sanitize error messages** (don't leak sensitive info)
4. **Rate limit registration attempts**
5. **Monitor for suspicious activity**
6. **Log all operations** (success and failures)
7. **Implement proper error handling**
8. **Test on real devices** before production

## Support

For issues:
- Check browser console for errors
- Review server logs: `storage/logs/security.log`
- Check documentation: `docs/PASSKEY_AUTHENTICATION_GUIDE.md`
- Contact: security@catvrf.ru
