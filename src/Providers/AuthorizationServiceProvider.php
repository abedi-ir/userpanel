<?php

namespace Jalno\Userpanel\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Auth\Access\Gate;
use Jalno\Userpanel\Models\User;

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
}
