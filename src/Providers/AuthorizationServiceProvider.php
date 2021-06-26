<?php

namespace Jalno\Userpanel\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Auth\Access\Gate;
use Jalno\Userpanel\Models\{User, UserType};

class AuthorizationServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        app(Gate::class)->before(function(User $user, string $ability, array $arguments) {
            return $user->canAbility($ability, $arguments);
        });
    }

    /**
     * Boot the authentication services for the application.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
