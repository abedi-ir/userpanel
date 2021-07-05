<?php

namespace Jalno\Userpanel\Http\Controllers;

use Jalno\Userpanel\API\UserTypes;
use Illuminate\Http\{Request, Response};
use Jalno\Userpanel\Models\{UserType, User};
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UserTypesController
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

    public function findByID(Request $request, $id): Response
    {
        $this->api->forUser($request->user());

        $usertype = $this->api->find($id);

        if (!$usertype) {
            throw new NotFoundHttpException();
        }

        return response(array(
            "status" => true,
            "user" => $usertype->makeHidden("priorities"),
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
            "user" => $usertype,
        ));
    }

    public function edit(Request $request, $id): Response
    {
        $this->api->forUser($request->user());

        $usertype = $this->api->edit(array_merge(["usertype" => $id], $request->all()));

        return response(array(
            "status" => true,
            "user" => $usertype,
        ));
    }

    public function delete(Request $request, $id): Response
    {
        $this->api->forUser($request->user());

        $this->api->delete($id);

        return response(["status" => true]);
    }
}
