<?php

namespace Jalno\Userpanel\Http\Controllers;

use Jalno\Userpanel\API\Users;
use Jalno\Userpanel\Models\User;
use Laravel\Lumen\Routing\Controller;
use Illuminate\Http\{Request, Response};
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
class UsersController extends Controller
{

    protected Users $api;

    public function __construct(Users $api)
    {
        $this->api = $api;
    }

    public function dashboard(Request $request): Response
    {
        $user = $request->user();
        return response([
            "status" => true,
            "user" => $user,
            "permissions" => $user->abilities()->pluck("name")->all(),
        ]);
    }

    public function search(Request $request): Response
    {
        $this->api->forUser($request->user());
        $parameters = $request->all();
        $limit = 5;
        if (isset($parameters['_limit'])) {
            $limit = intval($parameters['_limit']);
            unset($parameters['_limit']);
        }
		if (isset($parameters["cursor"])) {
			unset($parameters["cursor"]);
		}
        $response = $this->api->search($parameters, $limit);
        return response($response);
    }

    /**
     * @param int|string $id
     */
    public function findByID(Request $request, $id): Response
    {
        if (!is_numeric($id)) {
            throw new NotFoundHttpException();
        }
        $this->api->forUser($request->user());

        $user = $this->api->find((int) $id);
        if (!$user) {
            throw new NotFoundHttpException();
        }

        return response(array(
            "status" => true,
            "user" => $user,
        ));
    }

    public function add(Request $request): Response
    {
        $this->api->forUser($request->user());

        $user = $this->api->add($request->all());

        return response(array(
            "status" => true,
            "user" => $user,
        ));
    }

    /**
     * @param int|string $id
     */
    public function edit(Request $request, $id): Response
    {
        if (!is_numeric($id)) {
            throw new NotFoundHttpException();
        }
        $this->api->forUser($request->user());

        $user = $this->api->edit((int) $id, $request->all());

        return response(array(
            "status" => true,
            "user" => $user,
        ));
    }

    /**
     * @param int|string $id
     */
    public function delete(Request $request, $id): Response
    {
        $this->api->forUser($request->user());

        $this->api->delete((int) $id);

        return response(["status" => true]);
    }

    public function online(Request $request): Response
    {
        $this->api->forUser($request->user());

        $this->api->online();

        return response(["status" => true]);
    }
}
