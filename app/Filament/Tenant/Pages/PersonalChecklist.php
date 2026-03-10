<?php

namespace App\Filament\Tenant\Pages;

use Filament\Pages\Page;
use App\Models\Common\HealthRecommendation;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class PersonalChecklist extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-check-circle';
    protected static string $view = 'filament.tenant.pages.personal-checklist';
    protected static ?string $title = 'Мой План Здоровья (Checklist)';
    protected static ?string $navigationGroup = 'Personal';

    public $tasks = [];

    public function mount()
    {
        $this->loadTasks();
    }

    public function loadTasks()
    {
        $this->tasks = HealthRecommendation::where('user_id', Auth::id())
            ->where('is_completed', false)
            ->where('next_due_date', '<=', now()->addWeek()) // Показываем на неделю вперед
            ->orderBy('next_due_date', 'asc')
            ->get();
    }

    public function toggleComplete($taskId)
    {
        $task = HealthRecommendation::findOrFail($taskId);
        $task->completeRecommendation();
        
        Notification::make()
            ->title('Задача выполнена!')
            ->success()
            ->send();

        $this->loadTasks();
    }

    /** Группировка по частоте для UI */
    public function getGroupedTasksProperty()
    {
        return $this->tasks->groupBy('frequency');
    }
}
