<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Shared\Domain\Events\IEventPublisher;
use App\Shared\Infrastructure\Persistence\EventStore;
use App\Shared\Application\Services\EventDispatcherService;

final class EventSystemServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register Event Publisher (Outbox Pattern)
        $this->app->singleton(IEventPublisher::class, EventStore::class);

        // Register Event Dispatcher Service
        $this->app->singleton(EventDispatcherService::class, function ($app) {
            return new EventDispatcherService(
                $app->make('events'),
                $app->make(IEventPublisher::class),
            );
        });
    }

    public function boot(): void
    {
        // Temporarily disabled to allow migrations to run
        // TODO: Fix PublishOutboxMessagesJob and ProcessDeadLetterQueueJob instantiation
        /*
        // Schedule outbox publishing job to run every minute
        if ($this->app->runningInConsole()) {
            $this->app->booted(function () {
                $schedule = $this->app->make(\Illuminate\Console\Scheduling\Schedule::class);
                $schedule->job(\App\Jobs\PublishOutboxMessagesJob::class, 'emergency')
                    ->everyMinute()
                    ->description('Publish emergency outbox messages');

                $schedule->job(\App\Jobs\PublishOutboxMessagesJob::class, 'payment')
                    ->everyMinute()
                    ->description('Publish payment outbox messages');

                $schedule->job(\App\Jobs\PublishOutboxMessagesJob::class)
                    ->everyMinute()
                    ->description('Publish standard outbox messages');

                // Schedule DLQ processing every hour
                $schedule->job(new \App\Jobs\ProcessDeadLetterQueueJob(50, false))
                    ->hourly()
                    ->description('Process Dead Letter Queue');
            });
        }
        */
    }
}
