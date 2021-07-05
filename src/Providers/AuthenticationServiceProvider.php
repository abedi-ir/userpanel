<?php

namespace Jalno\Userpanel\Providers;

use Illuminate\Support\ServiceProvider;
use Jalno\Userpanel\Http\Middleware\Authenticate;

class AuthenticationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->routeMiddleware([
            'auth' => Authenticate::class,
        ]);
    }
}
