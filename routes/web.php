<?php

use Illuminate\Container\Container;
use Jalno\Userpanel\Http\Controllers\{
    UsersController,
    LoginController,
    UserTypesController,
    ConfigController
};

/** @var \Laravel\Lumen\Routing\Router|\Illuminate\Contracts\Routing\Registrar */
$router = Container::getInstance()->make("router");

$router->post("/register", array('uses' => LoginController::class . "@register"));

$router->group(['prefix' => '/userpanel', 'middleware' => 'auth'], function($router) {

    $router->get("/", array('uses' => UsersController::class . "@dashboard"));
    $router->get("/users", array('uses' => UsersController::class . "@search"));
    $router->post("/users", array('uses' => UsersController::class . "@add"));
    $router->get("/users/{id}", array('uses' => UsersController::class . "@findByID"));
    $router->put("/users/{id}", array('uses' => UsersController::class . "@edit"));
    $router->delete("/users/{id}", array('uses' => UsersController::class . "@delete"));

    $router->get("/usertypes", array('uses' => UserTypesController::class . "@search"));
    $router->post("/usertypes", array('uses' => UserTypesController::class . "@add"));
    $router->get("/usertypes/{id}", array('uses' => UserTypesController::class . "@findByID"));
    $router->put("/usertypes/{id}", array('uses' => UserTypesController::class . "@edit"));
    $router->delete("/usertypes/{id}", array('uses' => UserTypesController::class . "@delete"));

    $router->get("/config", array('uses' => ConfigController::class . "@search"));
    $router->get("/config/{config}", array('uses' => ConfigController::class . "@byConfig"));
    $router->put("/config", array('uses' => ConfigController::class . "@update"));
    $router->put("/config/{config}", array('uses' => ConfigController::class . "@updateByConfig"));
});
