<?php

namespace App\Livewire\Support;

use Livewire\Component;
use App\Services\Common\Support\HelpdeskService;
use Illuminate\Support\Facades\DB;

class ChatComponent extends Component
{
    public int $chatId;
    public string $message = '';
    public $messages = [];

    // При прослушивании событий (через Pusher или Livewire polling)
    protected $rules = [
        'message' => 'required|min:1',
    ];

    public function mount(int $chatId)
    {
        $this->chatId = $chatId;
        $this->loadMessages();
    }

    public function loadMessages()
    {
        $this->messages = DB::table('platform_chat_messages')
            ->where('platform_chat_id', $this->chatId)
            ->oldest()
            ->get()
            ->toArray();
    }

    public function sendMessage(HelpdeskService $helpdesk)
    {
        $this->validate();

        $helpdesk->sendChatMessage($this->chatId, auth()->id(), $this->message);

        $this->message = '';
        $this->loadMessages();
        
        // В 2026 мы бы использовали dispatch('chat-sent') для мгновенного обновления через Websockets
    }

    public function render()
    {
        return view('livewire.support.chat-component', [
            'msgs' => $this->messages
        ]);
    }
}
