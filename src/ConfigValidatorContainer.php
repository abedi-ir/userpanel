<?php
namespace Jalno\Userpanel;

use Psr\Container\NotFoundExceptionInterface;
use Illuminate\Contracts\Container\Container;

class ConfigValidatorContainer implements Contracts\IConfigValidatorContainer
{
	protected Container $app;
    /**
     * @var array<string,callable> $validations
     */
	protected array $validations = [];

	public function __construct(Container $app)
	{
		$this->app = $app;
	}

    public function add(string $name, callable $callback): void
    {
        $this->validations[$name] = $callback;
    }

	public function all(): array
	{
		return array_keys($this->validations);
    }
    
    public function has(string $id)
    {
        return isset($this->validations[$id]);
    }

    public function get(string $id)
    {
        if (!$this->has($id)) {
            throw new class("NotFound config with id: " . $id) extends \InvalidArgumentException implements NotFoundExceptionInterface {};
        }

        return $this->validations[$id];
    }
}
