<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeftIcon, PaperAirplaneIcon } from '@heroicons/vue/24/outline';

const form = useForm({
    email: '',
});

const isLoading = ref(false);
const emailSent = ref(false);

const submit = () => {
    isLoading.value = true;
    form.post('/api/v1/auth/forgot-password', {
        onFinish: () => {
            isLoading.value = false;
        },
        onSuccess: () => {
            emailSent.value = true;
        },
    });
};
</script>

<template>
    <Head title="Восстановление пароля" />

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

            <!-- Forgot Password Card -->
            <div class="bg-white rounded-2xl shadow-xl p-8">
                <!-- Back Button -->
                <Link href="/login" class="inline-flex items-center text-gray-600 hover:text-gray-900 mb-6 transition-colors">
                    <ArrowLeftIcon class="h-5 w-5 mr-2" />
                    Назад к входу
                </Link>

                <h2 class="text-2xl font-bold text-gray-900 mb-2">Восстановление пароля</h2>
                <p class="text-gray-600 mb-6">
                    Введите email, указанный при регистрации, и мы отправим вам ссылку для сброса пароля.
                </p>

                <!-- Success Message -->
                <div v-if="emailSent" class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                    <div class="flex items-start">
                        <svg class="h-5 w-5 text-green-500 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <div>
                            <p class="font-medium text-green-800">Ссылка отправлена!</p>
                            <p class="text-sm text-green-700 mt-1">
                                Проверьте почту {{ form.email }} и перейдите по ссылке для сброса пароля.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Form -->
                <form v-if="!emailSent" @submit.prevent="submit" class="space-y-5">
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
                            Отправка...
                        </span>
                        <span v-else class="flex items-center justify-center">
                            <PaperAirplaneIcon class="h-5 w-5 mr-2" />
                            Отправить ссылку
                        </span>
                    </button>
                </form>

                <!-- Resend Link (if email sent) -->
                <div v-else class="space-y-4">
                    <p class="text-sm text-gray-600">
                        Не получили письмо? Проверьте папку "Спам" или
                        <button
                            @click="emailSent = false"
                            class="text-blue-600 hover:text-blue-800 font-medium transition-colors"
                        >
                            отправить снова
                        </button>
                    </p>
                    
                    <Link
                        href="/login"
                        class="inline-flex items-center justify-center w-full py-3 px-4 bg-gray-100 text-gray-700 font-semibold rounded-lg hover:bg-gray-200 transition-colors"
                    >
                        Вернуться к входу
                    </Link>
                </div>
            </div>

            <!-- Footer -->
            <p class="mt-6 text-center text-xs text-gray-500">
                © 2026 CatVRF. Все права защищены.
            </p>
        </div>
    </div>
</template>
