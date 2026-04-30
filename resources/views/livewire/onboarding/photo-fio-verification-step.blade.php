<div class="max-w-2xl mx-auto p-6">
    <div class="bg-white rounded-lg shadow-lg p-8">
        <h2 class="text-2xl font-bold mb-6">Верификация личности</h2>
        
        @if (session('success'))
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded mb-6">
                {{ session('success') }}
            </div>
        @endif

        @if (session('warning'))
            <div class="bg-yellow-50 border border-yellow-200 text-yellow-700 px-4 py-3 rounded mb-6">
                {{ session('warning') }}
            </div>
        @endif

        @if (session('error'))
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-6">
                {{ session('error') }}
            </div>
        @endif

        @if ($error)
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-6">
                {{ $error }}
            </div>
        @endif

        @if (!empty($verificationResult))
            <div class="mb-6 p-4 rounded-lg {{ $verificationResult['success'] ? 'bg-green-50 border-green-200' : ($verificationResult['result'] === 'requires_review' ? 'bg-yellow-50 border-yellow-200' : 'bg-red-50 border-red-200') }}">
                <h3 class="font-semibold mb-2">Результат верификации</h3>
                <div class="text-sm space-y-1">
                    <p>Статус: {{ $verificationResult['result'] }}</p>
                    <p>Оценка: {{ number_format($verificationResult['score'] * 100, 1) }}%</p>
                    @if (isset($verificationResult['liveness_score']))
                        <p>Проверка живости: {{ number_format($verificationResult['liveness_score'] * 100, 1) }}%</p>
                    @endif
                    @if (isset($verificationResult['deepfake_score']))
                        <p>Проверка на deepfake: {{ number_format((1 - $verificationResult['deepfake_score']) * 100, 1) }}%</p>
                    @endif
                </div>
            </div>
        @endif

        <div class="space-y-4">
            <!-- Consent -->
            <div class="bg-blue-50 border border-blue-200 p-4 rounded-lg">
                <label class="flex items-start space-x-3 cursor-pointer">
                    <input
                        type="checkbox"
                        wire:model.live="consentGiven"
                        wire:click="giveConsent"
                        class="mt-1 w-4 h-4 text-blue-600 rounded focus:ring-blue-500"
                    >
                    <span class="text-sm text-gray-700">
                        Я даю согласие на обработку персональных данных в соответствии с 
                        <a href="#" class="text-blue-600 underline">политикой конфиденциальности</a> 
                        и <a href="#" class="text-blue-600 underline">152-ФЗ</a>
                    </span>
                </label>
            </div>

            <!-- FIO -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Фамилия *</label>
                <input
                    type="text"
                    wire:model="lastName"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                    placeholder="Иванов"
                >
                @error('lastName') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Имя *</label>
                <input
                    type="text"
                    wire:model="firstName"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                    placeholder="Иван"
                >
                @error('firstName') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Отчество</label>
                <input
                    type="text"
                    wire:model="middleName"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                    placeholder="Иванович"
                >
                @error('middleName') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            <!-- Photo -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Фото лица *</label>
                <input
                    type="file"
                    wire:model="photo"
                    accept="image/*"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg"
                >
                <p class="text-xs text-gray-500 mt-1">Максимальный размер: 5MB. Формат: JPG, PNG</p>
                @error('photo') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            <!-- Passport Photo -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Фото с паспортом</label>
                <input
                    type="file"
                    wire:model="passportPhoto"
                    accept="image/*"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg"
                >
                <p class="text-xs text-gray-500 mt-1">Необязательно, но повышает точность верификации</p>
                @error('passportPhoto') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            <button
                wire:click="submitVerification"
                wire:loading.attr="disabled"
                class="w-full bg-blue-600 text-white py-3 rounded-lg font-semibold hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
            >
                {{ $isProcessing ? 'Верификация...' : 'Пройти верификацию' }}
            </button>
        </div>
    </div>
</div>
