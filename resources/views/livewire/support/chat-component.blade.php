<div class="chat-container">
    <div class="messages-list flex flex-col gap-2 h-96 overflow-y-auto mb-4 bg-gray-50 p-4 rounded-lg border border-gray-200" wire:poll.5s="loadMessages">
        @foreach($messages as $msg)
            <div @class([
                'flex flex-col max-w-[80%]',
                'self-end' => $msg->sender_id === auth()->id(),
                'self-start' => $msg->sender_id !== auth()->id()
            ])>
                <div @class([
                    'p-3 rounded-xl shadow-sm',
                    'bg-indigo-600 text-white' => $msg->sender_id === auth()->id(),
                    'bg-white text-gray-800' => $msg->sender_id !== auth()->id()
                ])>
                    <p class="text-sm">{{ $msg->message }}</p>
                </div>
                <span class="text-[10px] text-gray-400 mt-1 px-1">
                    {{ \Carbon\Carbon::parse($msg->created_at)->format('H:i') }}
                </span>
            </div>
        @endforeach
    </div>

    <!-- Поле ввода -->
    <div class="chat-input flex gap-2">
        <input 
            type="text" 
            wire:model.defer="message" 
            wire:keydown.enter="sendMessage"
            placeholder="Введите сообщение..." 
            class="flex-1 rounded-lg border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 shadow-sm sm:text-sm"
        >
        <button 
            wire:click="sendMessage" 
            class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-150 ease-in-out"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
              <path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z" />
            </svg>
        </button>
    </div>
</div>
