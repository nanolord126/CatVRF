<div class="max-w-4xl mx-auto p-6">
    <div class="bg-white rounded-lg shadow-lg p-8">
        <h2 class="text-2xl font-bold mb-6">Регистрация бизнеса</h2>
        
        <!-- Progress Bar -->
        <div class="mb-8">
            <div class="w-full bg-gray-200 rounded-full h-2">
                <div class="bg-blue-600 h-2 rounded-full transition-all duration-300" style="width: {{ $progress }}%"></div>
            </div>
            <div class="flex justify-between mt-2 text-sm text-gray-600">
                <span>Шаг 1: ИНН</span>
                <span>Шаг 2: Документы</span>
                <span>Шаг 3: Проверка</span>
            </div>
        </div>

        @if ($step === 'inn')
            <!-- Step 1: INN Validation -->
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">ИНН организации</label>
                    <input
                        type="text"
                        wire:model.live="inn"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="Введите ИНН (10 или 12 цифр)"
                        maxlength="12"
                    >
                    @error('inn') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                @if ($innError)
                    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
                        {{ $innError }}
                    </div>
                @endif

                <button
                    wire:click="validateInn"
                    class="w-full bg-blue-600 text-white py-3 rounded-lg font-semibold hover:bg-blue-700 transition-colors"
                >
                    Проверить ИНН
                </button>
            </div>

        @elseif ($step === 'documents')
            <!-- Step 2: Document Upload -->
            <div class="space-y-6">
                <div class="bg-blue-50 border border-blue-200 p-4 rounded-lg">
                    <h3 class="font-semibold mb-2">{{ $companyData['name'] ?? 'Компания' }}</h3>
                    <p class="text-sm text-gray-600">ИНН: {{ $companyData['inn'] ?? '' }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Выписка ЕГРЮЛ (PDF или изображение) *</label>
                    <input
                        type="file"
                        wire:model="egrulDocument"
                        accept=".pdf,.jpg,.jpeg,.png"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg"
                    >
                    @error('egrulDocument') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Паспорт директора (PDF или изображение)</label>
                    <input
                        type="file"
                        wire:model="passportDocument"
                        accept=".pdf,.jpg,.jpeg,.png"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg"
                    >
                    @error('passportDocument') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Селфи с паспортом *</label>
                    <input
                        type="file"
                        wire:model="selfiePhoto"
                        accept="image/*"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg"
                    >
                    @error('selfiePhoto') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <div class="flex space-x-4">
                    <button
                        wire:click="$set('step', 'inn')"
                        class="flex-1 bg-gray-200 text-gray-700 py-3 rounded-lg font-semibold hover:bg-gray-300 transition-colors"
                    >
                        Назад
                    </button>
                    <button
                        wire:click="uploadDocuments"
                        class="flex-1 bg-blue-600 text-white py-3 rounded-lg font-semibold hover:bg-blue-700 transition-colors"
                    >
                        Загрузить документы
                    </button>
                </div>
            </div>

        @elseif ($step === 'review')
            <!-- Step 3: Review -->
            <div class="space-y-6">
                <div class="bg-green-50 border border-green-200 p-4 rounded-lg">
                    <h3 class="font-semibold text-green-800 mb-2">Документы загружены</h3>
                    <ul class="text-sm text-green-700 space-y-1">
                        @foreach ($uploadedDocuments as $type => $doc)
                            <li>{{ ucfirst($type) }}: {{ $doc['verification']['success'] ? '✓ Проверено' : '⚠ Требует проверки' }}</li>
                        @endforeach
                    </ul>
                </div>

                <div class="flex space-x-4">
                    <button
                        wire:click="$set('step', 'documents')"
                        class="flex-1 bg-gray-200 text-gray-700 py-3 rounded-lg font-semibold hover:bg-gray-300 transition-colors"
                    >
                        Назад
                    </button>
                    <button
                        wire:click="submitRegistration"
                        class="flex-1 bg-green-600 text-white py-3 rounded-lg font-semibold hover:bg-green-700 transition-colors"
                    >
                        Отправить на модерацию
                    </button>
                </div>
            </div>

        @elseif ($step === 'complete')
            <!-- Complete -->
            <div class="text-center py-12">
                <div class="text-green-500 text-6xl mb-4">✓</div>
                <h3 class="text-2xl font-bold text-gray-800 mb-2">Регистрация отправлена</h3>
                <p class="text-gray-600 mb-6">Ваша заявка будет рассмотрена в течение 1-3 рабочих дней</p>
                <a href="{{ route('dashboard') }}" class="inline-block bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-blue-700 transition-colors">
                    Перейти в dashboard
                </a>
            </div>
        @endif
    </div>
</div>
