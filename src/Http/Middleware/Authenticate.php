<?php

namespace Jalno\Userpanel\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Jalno\Userpanel\Models\User;
use Illuminate\Contracts\Auth\Factory as Auth;

class Authenticate
{
    /**
     * The authentication guard factory instance.
     *
     * @var Auth
     */
    protected $auth;

    /**
     * Create a new middleware instance.
     *
     * @param  Auth  $auth
     * @return void
     */
    public function __construct(Auth $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Handle an incoming request.
     *
     * @param  Request      $request
     * @param  Closure      $next
     * @param  string|null  $guard
     * @return mixed
     */
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
