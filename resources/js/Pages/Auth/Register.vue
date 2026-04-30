<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { EyeIcon, EyeSlashIcon, CheckIcon, XMarkIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    inviteCode: String,
});

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    phone: '',
    invite_code: props.inviteCode || '',
    agree_terms: false,
});

const showPassword = ref(false);
const showConfirmPassword = ref(false);
const isLoading = ref(false);

const passwordRequirements = computed(() => [
    { label: 'Минимум 8 символов', valid: form.password.length >= 8 },
    { label: 'Заглавная буква', valid: /[A-Z]/.test(form.password) },
    { label: 'Строчная буква', valid: /[a-z]/.test(form.password) },
    { label: 'Цифра', valid: /[0-9]/.test(form.password) },
    { label: 'Специальный символ', valid: /[!@#$%^&*(),.?":{}|<>]/.test(form.password) },
]);

const allRequirementsMet = computed(() => passwordRequirements.value.every(req => req.valid));

const submit = () => {
    if (!allRequirementsMet.value) {
        return;
    }

    isLoading.value = true;
    form.post('/api/v1/auth/register', {
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

const toggleConfirmPassword = () => {
    showConfirmPassword.value = !showConfirmPassword.value;
};
</script>

<template>
    <Head title="Регистрация" />

    <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-blue-50 via-white to-purple-50 px-4 py-8">
        <div class="max-w-2xl w-full">
            <!-- Logo -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-blue-600 to-purple-600 rounded-2xl shadow-lg mb-4">
                    <span class="text-white text-2xl font-bold">🐱</span>
                </div>
                <h1 class="text-3xl font-bold text-gray-900">CatVRF</h1>
                <p class="text-gray-600 mt-2">Медицинский маркетплейс с AI</p>
            </div>

            <!-- Register Card -->
            <div class="bg-white rounded-2xl shadow-xl p-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-6">Регистрация</h2>

                <!-- Form -->
                <form @submit.prevent="submit" class="space-y-5">
                    <!-- Name -->
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                            Имя
                        </label>
                        <input
                            id="name"
                            v-model="form.name"
                            type="text"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                            placeholder="Иван Иванов"
                            required
                            autocomplete="name"
                        />
                        <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">
                            {{ form.errors.name }}
                        </p>
                    </div>

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

                    <!-- Phone -->
                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">
                            Телефон (опционально)
                        </label>
                        <input
                            id="phone"
                            v-model="form.phone"
                            type="tel"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                            placeholder="+7 (999) 123-45-67"
                            autocomplete="tel"
                        />
                        <p v-if="form.errors.phone" class="mt-1 text-sm text-red-600">
                            {{ form.errors.phone }}
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
                                autocomplete="new-password"
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

                        <!-- Password Requirements -->
                        <div v-if="form.password" class="mt-3 space-y-2">
                            <div
                                v-for="(req, index) in passwordRequirements"
                                :key="index"
                                class="flex items-center text-sm"
                            >
                                <CheckIcon v-if="req.valid" class="h-4 w-4 text-green-500 mr-2" />
                                <XMarkIcon v-else class="h-4 w-4 text-red-500 mr-2" />
                                <span :class="req.valid ? 'text-green-700' : 'text-red-700'">
                                    {{ req.label }}
                                </span>
                            </div>
                        </div>

                        <p v-if="form.errors.password" class="mt-1 text-sm text-red-600">
                            {{ form.errors.password }}
                        </p>
                    </div>

                    <!-- Confirm Password -->
                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-2">
                            Подтвердите пароль
                        </label>
                        <div class="relative">
                            <input
                                id="password_confirmation"
                                v-model="form.password_confirmation"
                                :type="showConfirmPassword ? 'text' : 'password'"
                                class="w-full px-4 py-3 pr-12 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                placeholder="••••••••"
                                required
                                autocomplete="new-password"
                            />
                            <button
                                type="button"
                                @click="toggleConfirmPassword"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors"
                            >
                                <EyeIcon v-if="!showConfirmPassword" class="h-5 w-5" />
                                <EyeSlashIcon v-else class="h-5 w-5" />
                            </button>
                        </div>
                        <p v-if="form.password_confirmation && form.password !== form.password_confirmation" class="mt-1 text-sm text-red-600">
                            Пароли не совпадают
                        </p>
                    </div>

                    <!-- Invite Code -->
                    <div>
                        <label for="invite_code" class="block text-sm font-medium text-gray-700 mb-2">
                            Код приглашения (опционально)
                        </label>
                        <input
                            id="invite_code"
                            v-model="form.invite_code"
                            type="text"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                            placeholder="Введите код приглашения"
                        />
                        <p v-if="form.errors.invite_code" class="mt-1 text-sm text-red-600">
                            {{ form.errors.invite_code }}
                        </p>
                    </div>

                    <!-- Terms Agreement -->
                    <div class="flex items-start">
                        <div class="flex items-center h-5">
                            <input
                                id="agree_terms"
                                v-model="form.agree_terms"
                                type="checkbox"
                                class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 mt-0.5"
                                required
                            />
                        </div>
                        <div class="ml-3 text-sm">
                            <label for="agree_terms" class="text-gray-600">
                                Я согласен с
                                <Link href="/terms" class="text-blue-600 hover:text-blue-800 transition-colors">
                                    условиями использования
                                </Link>
                                и
                                <Link href="/privacy" class="text-blue-600 hover:text-blue-800 transition-colors">
                                    политикой конфиденциальности
                                </Link>
                            </label>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button
                        type="submit"
                        :disabled="form.processing || isLoading || !allRequirementsMet || !form.agree_terms"
                        class="w-full py-3 px-4 bg-gradient-to-r from-blue-600 to-purple-600 text-white font-semibold rounded-lg hover:from-blue-700 hover:to-purple-700 focus:ring-4 focus:ring-blue-300 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <span v-if="isLoading" class="flex items-center justify-center">
                            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Регистрация...
                        </span>
                        <span v-else>Зарегистрироваться</span>
                    </button>
                </form>

                
                <!-- Login Link -->
                <p class="mt-6 text-center text-sm text-gray-600">
                    Уже есть аккаунт?
                    <Link href="/login" class="text-blue-600 hover:text-blue-800 font-medium transition-colors">
                        Войти
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
