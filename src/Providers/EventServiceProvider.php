<?php

namespace Jalno\Userpanel\Providers;

use Laravel\Lumen\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array<class-string,class-string>|array<string,string-class> $listen
     */
    protected $listen = [
        /* \Jalno\Userpanel\Events\ExampleEvent::class => [
            \Jalno\Userpanel\Listeners\ExampleListener::class,
        ], */
    ];
}
