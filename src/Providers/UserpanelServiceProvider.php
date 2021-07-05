<?php

namespace Jalno\Userpanel\Providers;

use Jalno\Userpanel\Exceptions;
use Illuminate\Support\ServiceProvider;
use Jalno\Userpanel\Rules\ConfigValidators;
use Jalno\Userpanel\ConfigValidatorContainer;
use Jalno\Userpanel\Contracts\IConfigValidatorContainer;

class UserpanelServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(IConfigValidatorContainer::class, ConfigValidatorContainer::class);
    }

    /**
     * Boot the config validator service for the application.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadRoutesFrom(__DIR__ . "/../../routes/web.php");
        $this->loadMigrationsFrom(__DIR__ . "/../../database/migrations");

        
        $validators = new ConfigValidators($this->app);
        $validators->addValidators();
    }
}
