<script setup>
import { ref, onMounted } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { EyeIcon, EyeSlashIcon, FingerPrintIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    canResetPassword: Boolean,
    status: String,
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const showPassword = ref(false);
const isLoading = ref(false);
const isPasskeySupported = ref(false);
const isPasskeyLoading = ref(false);

const submit = () => {
    isLoading.value = true;
    form.post('/api/v1/auth/login', {
        onFinish: () => {
            isLoading.value = false;
        },
        onSuccess: () => {
            window.location.href = '/';
        },
    });
};

const togglePassword = () => {
    showPassword.value = !showPassword.value;
};

// Check for WebAuthn/Passkey support
onMounted(() => {
    isPasskeySupported.value = 
        window.PublicKeyCredential !== undefined &&
        typeof window.PublicKeyCredential === 'function' &&
        typeof window.PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable === 'function';
    
    if (isPasskeySupported.value) {
        window.PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable()
            .then((available) => {
                isPasskeySupported.value = available;
            })
            .catch(() => {
                isPasskeySupported.value = false;
            });
    }
});

const signInWithPasskey = async () => {
    isPasskeyLoading.value = true;
    try {
        const response = await fetch('/api/v1/auth/passkey/challenge', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ email: form.email }),
        });

        if (!response.ok) {
            throw new Error('Failed to get passkey challenge');
        }

        const { challenge } = await response.json();

        const credential = await navigator.credentials.get({
            publicKey: {
                challenge: Uint8Array.from(atob(challenge), c => c.charCodeAt(0)),
                rpId: window.location.hostname,
                userVerification: 'preferred',
                timeout: 60000,
            },
        });

        const authResponse = await fetch('/api/v1/auth/passkey/verify', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                credentialId: btoa(String.fromCharCode(...new Uint8Array(credential.rawId))),
                clientDataJSON: btoa(String.fromCharCode(...new Uint8Array(credential.response.clientDataJSON))),
                authenticatorData: btoa(String.fromCharCode(...new Uint8Array(credential.response.authenticatorData))),
                signature: btoa(String.fromCharCode(...new Uint8Array(credential.response.signature))),
                userHandle: credential.response.userHandle ? btoa(String.fromCharCode(...new Uint8Array(credential.response.userHandle))) : null,
            }),
        });

        if (authResponse.ok) {
            window.location.href = '/';
        } else {
            throw new Error('Passkey verification failed');
        }
    } catch (error) {
        console.error('Passkey error:', error);
        alert('Ошибка при входе через Passkey: ' + error.message);
    } finally {
        isPasskeyLoading.value = false;
    }
};
</script>

<template>
    <Head title="Вход в систему" />

    <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-blue-50 via-white to-purple-50 px-4">
        <div class="max-w-md w-full">
            <!-- Logo -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-blue-600 to-purple-600 rounded-2xl shadow-lg mb-4">
                    <span class="text-white text-2xl font-bold">🐱</span>
                </div>
                <h1 class="text-3xl font-bold text-gray-900">CatVRF</h1>
                <p class="text-gray-600 mt-2">Медицинский маркетплейс с AI</p>
            </div>

            <!-- Login Card -->
            <div class="bg-white rounded-2xl shadow-xl p-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-6">Вход в систему</h2>

                <!-- Status Message -->
                <div v-if="status" class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg">
                    <p class="text-sm text-green-800">{{ status }}</p>
                </div>

                <!-- Form -->
                <form @submit.prevent="submit" class="space-y-5">
                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                            Email
                        </label>
                        <input
                            id="email"
                            v-model="form.email"
                            type="email"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                            placeholder="your@email.com"
                            required
                            autocomplete="email"
                        />
                        <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">
                            {{ form.errors.email }}
                        </p>
                    </div>

                    <!-- Password -->
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                            Пароль
                        </label>
                        <div class="relative">
                            <input
                                id="password"
                                v-model="form.password"
                                :type="showPassword ? 'text' : 'password'"
                                class="w-full px-4 py-3 pr-12 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                placeholder="••••••••"
                                required
                                autocomplete="current-password"
                            />
                            <button
                                type="button"
                                @click="togglePassword"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors"
                            >
                                <EyeIcon v-if="!showPassword" class="h-5 w-5" />
                                <EyeSlashIcon v-else class="h-5 w-5" />
                            </button>
                        </div>
                        <p v-if="form.errors.password" class="mt-1 text-sm text-red-600">
                            {{ form.errors.password }}
                        </p>
                    </div>

                    <!-- Remember & Forgot Password -->
                    <div class="flex items-center justify-between">
                        <label class="flex items-center">
                            <input
                                v-model="form.remember"
                                type="checkbox"
                                class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                            />
                            <span class="ml-2 text-sm text-gray-600">Запомнить меня</span>
                        </label>

                        <Link
                            v-if="canResetPassword"
                            href="/forgot-password"
                            class="text-sm text-blue-600 hover:text-blue-800 transition-colors"
                        >
                            Забыли пароль?
                        </Link>
                    </div>

                    <!-- Submit Button -->
                    <button
                        type="submit"
                        :disabled="form.processing || isLoading"
                        class="w-full py-3 px-4 bg-gradient-to-r from-blue-600 to-purple-600 text-white font-semibold rounded-lg hover:from-blue-700 hover:to-purple-700 focus:ring-4 focus:ring-blue-300 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <span v-if="isLoading" class="flex items-center justify-center">
                            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Вход...
                        </span>
                        <span v-else>Войти</span>
                    </button>

                    <!-- Passkey Login -->
                    <button
                        v-if="isPasskeySupported"
                        type="button"
                        @click="signInWithPasskey"
                        :disabled="isPasskeyLoading || !form.email"
                        class="w-full py-3 px-4 bg-gray-900 text-white font-semibold rounded-lg hover:bg-gray-800 focus:ring-4 focus:ring-gray-300 transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center"
                    >
                        <FingerPrintIcon class="h-5 w-5 mr-2" />
                        <span v-if="isPasskeyLoading">Вход через Face ID / Touch ID...</span>
                        <span v-else>Войти через Face ID / Touch ID</span>
                    </button>
                </form>

                <!-- Register Link -->
                <p class="mt-6 text-center text-sm text-gray-600">
                    Нет аккаунта?
                    <Link href="/register" class="text-blue-600 hover:text-blue-800 font-medium transition-colors">
                        Зарегистрироваться
                    </Link>
                </p>
            </div>

            <!-- Footer -->
            <p class="mt-6 text-center text-xs text-gray-500">
                © 2026 CatVRF. Все права защищены.
            </p>
        </div>
    </div>
</template>
