<?php

namespace Jalno\Userpanel\Http\Controllers;

use Jalno\Userpanel\API\Config;
use Illuminate\Http\{Request, Response};
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ConfigController
{
    protected Config $api;

    public function __construct(Config $api)
    {
        $this->api = $api;
    }

    public function search(Request $request): Response
    {
        $this->api->forUser($request->user());

        return response([
            "status" => true,
            "configs" => $this->api->search($request->all()),
        ]);
    }

    public function byConfig(Request $request, string $config): Response
    {
        $this->api->forUser($request->user());

        return response([
            "status" => true,
            $config => $this->api->search(["configs" => [$config]])[$config] ?? null,
        ]);
    }

    public function updateByConfig(Request $request, string $config): Response
    {
        $this->api->forUser($request->user());
        $this->api->update(array(
            "config" => array(
                [
                    "name" => $config,
                    "value" => $request->get("value"),
                ],
            ),
        ));
        return response([
            "status" => true,
        ]);
    }

    public function update(Request $request): Response
    {
        $this->api->forUser($request->user());
        $this->api->update($request->all());
        return response([
            "status" => true,
        ]);
    }
}
