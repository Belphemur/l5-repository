<?php

namespace Prettus\Repository\Providers;

use Illuminate\Support\ServiceProvider;
use Prettus\Repository\Events\RepositoryEntityCreated;
use Prettus\Repository\Events\RepositoryEntityDeleted;
use Prettus\Repository\Events\RepositoryEntityUpdated;
use Prettus\Repository\Listeners\CleanCacheRepository;

class EventServiceProvider extends ServiceProvider
{

    /**
     * The event handler mappings for the application.
     *
     * @var array
     */
    protected $listen
        = [
            RepositoryEntityCreated::class => [
                CleanCacheRepository::class,
            ],
            RepositoryEntityUpdated::class => [
                CleanCacheRepository::class,
            ],
            RepositoryEntityDeleted::class => [
                CleanCacheRepository::class,
            ],
        ];

    /**
     * Register the application's event listeners.
     *
     * @return void
     */
    public function boot()
    {
        $events = app('events');

        foreach ($this->listen as $event => $listeners) {
            foreach ($listeners as $listener) {
                $events->listen($event, $listener);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function register()
    {
        //
    }

    /**
     * Get the events and handlers.
     *
     * @return array
     */
    public function listens()
    {
        return $this->listen;
    }
}
