<?php
namespace Jalno\Userpanel;

use Laravel\Lumen\Routing\Router;
use Jalno\Userpanel\Http\Middleware;
use Jalno\Lumen\Packages\PackageAbstract;

class Package extends PackageAbstract {
	public function getProviders(): array
	{
		return [
			Providers\AppServiceProvider::class,
			Providers\AuthServiceProvider::class,
			Providers\EventServiceProvider::class,
			Providers\AuthorizationServiceProvider::class,
		];
	}

	public function basePath(): string
	{
		return __DIR__;
	}

	public function getNamespace(): string
	{
		return __NAMESPACE__;
	}

    public function setupRouter(Router $router): void
    {
		packages()->app->routeMiddleware([
			'auth' => Middleware\Authenticate::class,
		]);

		include_once $this->path("routes", "web.php");
	}
}
