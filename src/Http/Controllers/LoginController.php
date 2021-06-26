<?php

namespace Jalno\Userpanel\Http\Controllers;

use Jalno\Userpanel\API\Login;
use Laravel\Lumen\Routing\Controller;
use Illuminate\Http\{Request, Response};
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class LoginController extends Controller
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
