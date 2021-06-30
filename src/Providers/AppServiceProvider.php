<?php

namespace Jalno\Userpanel\Providers;

use Jalno\Userpanel\Exceptions;
use Illuminate\Support\ServiceProvider;
use Jalno\Userpanel\Rules\ConfigValidators;
use Jalno\Userpanel\ConfigValidatorContainer;
use Jalno\Userpanel\Contracts\IConfigValidatorContainer;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(IConfigValidatorContainer::class, ConfigValidatorContainer::class);
		if ($this->app->runningInConsole()) {
			$this->registerMigrations();
		}
    }

    /**
     * Boot the config validator service for the application.
     *
     * @return void
     */
    public function boot()
    {
        $validators = new ConfigValidators($this->app);
        $validators->addValidators();
    }

	public function registerMigrations()
	{
		$this->loadMigrationsFrom(package()->getMigrationPath());
	}
}
