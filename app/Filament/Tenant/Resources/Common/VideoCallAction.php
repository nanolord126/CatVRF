<?php

namespace App\Filament\Tenant\Resources\Common;

use Filament\Tables\Actions\Action;
use App\Models\VideoCall;
use App\Services\VideoCallService;
use App\Models\User;

class VideoCallAction
{
    public static function make(string $receiverIdField = 'user_id'): Action {
        return Action::make('start_video_call')
            ->label('🎥 Видеозвонок')
            ->icon('heroicon-o-video-camera')
            ->color('success')
            ->action(fn ($record) => self::initCall($record, $receiverIdField))
            ->requiresConfirmation();
    }

    protected static function initCall($record, $field) {
        $caller = auth()->user();
        $receiver = User::findOrFail($record->$field);
        $service = app(VideoCallService::class);
        $call = $service->createRoom($caller, $receiver);
        return redirect()->route('livewire.video-room', ['roomId' => $call->room_id]);
    }
}
