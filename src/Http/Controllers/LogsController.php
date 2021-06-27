<?php

namespace Jalno\Userpanel\Http\Controllers;

use Jalno\Userpanel\API\Logs;
use Jalno\Userpanel\Models\Log;
use Laravel\Lumen\Routing\Controller;
use Illuminate\Http\{Request, Response};
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class LogsController extends Controller
{

    protected Logs $api;

    public function __construct(Logs $api)
    {
        $this->api = $api;
    }

    public function search(Request $request): Response
    {
        $this->api->forUser($request->user());
        $parameters = $request->all();
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

    public function findByID(Request $request, $log): Response
    {
        $this->api->forUser($request->user());

        $log = $this->api->find($log);

        if (!$log) {
            throw new NotFoundHttpException();
        }

        return response(array(
            "status" => true,
            "log" => $log,
        ));
    }

    public function delete(Request $request, $log): Response
    {
        $this->api->forUser($request->user());

        $this->api->delete($log);

        return response(["status" => true]);
    }
}
