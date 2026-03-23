<!-- Modal compliance-connect.blade.php -->
<div 
    x-show="isModalOpen" 
    class="fixed inset-0 z-50 overflow-y-auto" 
    aria-labelledby="modal-title" 
    role="dialog" 
    aria-modal="true"
    x-cloak
>
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <!-- Backdrop -->
        <div 
            x-show="isModalOpen"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" 
            aria-hidden="true" 
            @click="isModalOpen = false"
        ></div>

        <!-- This element is to trick the browser into centering the modal contents. -->
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

        <!-- Modal panel -->
        <div 
            x-show="isModalOpen"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6 shadow-2xl"
        >
            <div>
                <div class="mt-3 text-center sm:mt-5">
                    <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                        Подключение: <span x-text="currentLabel"></span>
                    </h3>
                    <div class="mt-4 text-left space-y-4">
                        <div>
                            <label for="inn" class="block text-sm font-medium text-gray-700">ИНН Организации (12 цифр)</label>
                            <input type="text" x-model="formData.inn" id="inn" maxlength="12" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" placeholder="771234567890">
                        </div>
                        <div>
                            <label for="api_token" class="block text-sm font-medium text-gray-700">API Токен</label>
                            <textarea x-model="formData.api_token" id="api_token" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" placeholder="Вставьте токен из личного кабинета системы"></textarea>
                        </div>

                        <!-- Status Alert Box -->
                        <div x-show="status.message" :class="status.type === 'success' ? 'bg-green-50 text-green-700 border-green-200' : (status.type === 'error' ? 'bg-red-50 text-red-700 border-red-200' : 'bg-blue-50 text-blue-700 border-blue-200')" class="p-3 rounded border text-xs">
                            <span x-text="status.message"></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="mt-5 sm:mt-6 sm:grid sm:grid-cols-2 sm:gap-3 sm:grid-flow-row-dense">
                <button 
                    type="button" 
                    @click="testConnection()" 
                    :disabled="status.loading"
                    class="w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:col-start-1 sm:text-sm"
                >
                    <span x-show="!status.loading">Протестировать</span>
                    <span x-show="status.loading">Ждите...</span>
                </button>
                <button 
                    type="button" 
                    @click="saveConnection()" 
                    :disabled="status.loading"
                    class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:col-start-2 sm:text-sm disabled:opacity-50"
                >
                    Подключить
                </button>
            </div>
            <div class="mt-3">
                <button 
                    type="button" 
                    @click="isModalOpen = false" 
                    class="w-full inline-flex justify-center rounded-md border border-gray-200 shadow-sm px-4 py-2 bg-gray-50 text-base font-medium text-gray-500 hover:bg-gray-100 sm:text-sm"
                >
                    Отмена
                </button>
            </div>
        </div>
    </div>
</div>
