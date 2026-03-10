<?php

namespace App\Filament\Tenant\Resources\StaffTaskResource\Pages;

use App\Filament\Tenant\Resources\StaffTaskResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use App\Models\User;

class CreateStaffTask extends CreateRecord
{
    protected static string $resource = StaffTaskResource::class;

    protected function afterCreate(): void
    {
        $task = $this->record;

        if ($task->user_id) {
            $user = User::find($task->user_id);
            if ($user) {
                Notification::make()
                    ->title('New Task Assigned')
                    ->body("You have a new task: {$task->title}")
                    ->icon('heroicon-o-clipboard-document-check')
                    ->warning()
                    ->sendToDatabase($user);
            }
        }
    }
}
