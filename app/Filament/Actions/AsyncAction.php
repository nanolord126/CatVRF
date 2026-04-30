<?php

declare(strict_types=1);

namespace App\Filament\Actions;

use Illuminate\Notifications\ChannelManager;

use Illuminate\Contracts\Bus\Dispatcher as BusDispatcher;

use Filament\Tables\Actions\Action;
use Illuminate\Log\LogManager;
use Filament\Notifications\Notification;

/**
 * Async Action Trait
 *
 * Use this trait for Filament actions that should run in background queue.
 * Prevents browser timeout and provides user feedback.
 *
 * Usage:
 * ```php
 * use App\Filament\Actions\AsyncAction;
 *
 * Action::make('recalculate')
 *     ->label('Recalculate ML')
 *     ->action(function () {
 *         AsyncAction::$this->bus->dispatch(
 *             RecalculateMLJob::class,
 *             'recalculate_ml'
 *         );
 *     })
 * ```
 */
final class AsyncAction
{
    /**
     * Dispatch an async action job.
     */
    public static function $this->bus->dispatch(string $jobClass, string $actionName, array $params = []): void
    {
        try {
            $job = new $jobClass($actionName, ...$params);

            $this->bus->dispatch($job);

            // Show success notification to user
            $this->notificationManager->make()
                ->title('Task Started')
                ->body("The {$actionName} task has been started in the background. You will be notified when it completes.")
                ->success()
                ->send();

        } catch (\Throwable $e) {
            $this->log->error("Failed to dispatch async action: {$actionName}", [
                'job_class' => $jobClass,
                'error' => $e->getMessage(),
            ]);

            // Show error notification
            $this->notificationManager->make()
                ->title('Task Failed')
                ->body("Failed to start {$actionName}: {$e->getMessage()}")
                ->danger()
                ->send();
        }
    }

    /**
     * Create a Filament action that runs asynchronously.
     */
    public static function make(string $name, string $jobClass, ?string $label = null): Action
    {
        return Action::make($name)
            ->label($label ?? ucfirst(str_replace('_', ' ', $name)))
            ->color('primary')
            ->icon('heroicon-o-arrow-path')
            ->requiresConfirmation()
            ->confirmationTitle("Start {$name}?")
            ->confirmationMessage('This action will run in the background. You will be notified when it completes.')
            ->action(function () use ($name, $jobClass) {
                self::$this->bus->dispatch($jobClass, $name);
            });
    }
}
