<?php

namespace Jalno\Userpanel\Providers;

use Laravel\Passport\Token;
use Illuminate\Support\ServiceProvider;
use Jalno\Userpanel\Observers\TokenObserver;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Boot the authentication services for the application.
     *
     * @return void
     */
    public function boot()
    {
        Token::observe(TokenObserver::class);
    }
}
