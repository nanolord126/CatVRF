<div class="max-w-2xl mx-auto p-6">
    <div class="bg-white rounded-lg shadow-lg p-8">
        <h2 class="text-2xl font-bold mb-6">Регистрация филиала</h2>
        
        @if (session('success'))
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded mb-6">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-6">
                {{ session('error') }}
            </div>
        @endif

        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Родительский бизнес</label>
                <select
                    wire:model.live="parentTenantId"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                >
                    <option value="">Выберите бизнес</option>
                    @foreach ($tenants as $tenant)
                        <option value="{{ $tenant->id }}">{{ $tenant->name }} (ИНН: {{ $tenant->inn }})</option>
                    @endforeach
                </select>
                @error('parentTenantId') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">ИНН филиала</label>
                <div class="flex space-x-2">
                    <input
                        type="text"
                        wire:model.live="branchInn"
                        class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                        placeholder="Введите ИНН филиала"
                        maxlength="12"
                    >
                    <button
                        wire:click="validateInn"
                        class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors"
                    >
                        Проверить
                    </button>
                </div>
                @error('branchInn') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                @if ($innError)
                    <span class="text-red-500 text-sm">{{ $innError }}</span>
                @endif
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Название филиала</label>
                <input
                    type="text"
                    wire:model="branchName"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                    placeholder="Например: Филиал ООО Тест - Центральный"
                >
                @error('branchName') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Адрес филиала</label>
                <input
                    type="text"
                    wire:model="branchAddress"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                    placeholder="Город, улица, дом, офис"
                >
                @error('branchAddress') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            <button
                wire:click="submitBranch"
                wire:loading.attr="disabled"
                class="w-full bg-blue-600 text-white py-3 rounded-lg font-semibold hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
            >
                {{ $isSubmitting ? 'Регистрация...' : 'Зарегистрировать филиал' }}
            </button>
        </div>
    </div>
</div>
