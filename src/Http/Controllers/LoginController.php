<?php

namespace Jalno\Userpanel\Http\Controllers;

use Jalno\Userpanel\API\Login;
use Illuminate\Http\{Request, Response};

class LoginController
{
    protected Login $api;

    public function __construct(Login $api)
    {
        $this->api = $api;
    }
    
    public function register(Request $request): Response
    {

        $user = $this->api->register($request->all());

        return response(array(
            "status" => true,
            "user" => $user,
        ));
    }
    
}
