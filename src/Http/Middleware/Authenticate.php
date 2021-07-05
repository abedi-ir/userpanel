<?php

namespace Jalno\Userpanel\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Jalno\Userpanel\Models\User;
use Illuminate\Contracts\Auth\Factory as Auth;

class Authenticate
{
    protected Auth $auth;

    public function __construct(Auth $auth)
    {
        $this->auth = $auth;
    }

    public function handle(Request $request, Closure $next, ?string $guard = null)
    {
        if (!$request->user()) {
            return response(["message" => 'Unauthorized', "status" => false], 401);
        }

        if ($request->user()->status != User::ACTIVE) {
            return response(["message" => 'User is not active', "status" => false], 403);
        }

        return $next($request);
    }
}
