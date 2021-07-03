<?php

namespace Jalno\Userpanel\Http\Controllers;

use Jalno\Userpanel\API\UserTypes;
use Laravel\Lumen\Routing\Controller;
use Illuminate\Http\{Request, Response};
use Jalno\Userpanel\Models\{UserType, User};
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UserTypesController extends Controller
{

    protected UserTypes $api;

    public function __construct(UserTypes $api)
    {
        $this->api = $api;
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

        $usertype = $this->api->find((int) $id);

        if (!$usertype) {
            throw new NotFoundHttpException();
        }

        return response(array(
            "status" => true,
            "usertype" => $usertype->makeHidden("priorities"),
            "childrenTypes" => $usertype->childrenTypes(),
            "has_custom_permissions_users" => (new User)->where("usertype_id", $usertype->id)->where("has_custom_permissions", 1)->exists(),
        ));
    }

    public function add(Request $request): Response
    {
        $this->api->forUser($request->user());

        $usertype = $this->api->add($request->all());

        return response(array(
            "status" => true,
            "usertype" => $usertype,
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

        $usertype = $this->api->edit((int) $id, $request->all());

        return response(array(
            "status" => true,
            "usertype" => $usertype,
        ));
    }

    /**
     * @param int|string $id
     */
    public function delete(Request $request, $id): Response
    {
        if (!is_numeric($id)) {
            throw new NotFoundHttpException();
        }
        $this->api->forUser($request->user());

        $this->api->delete((int) $id);

        return response(["status" => true]);
    }
}
