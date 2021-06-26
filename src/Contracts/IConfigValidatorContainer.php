<?php
namespace Jalno\Userpanel\Contracts;

use Psr\Container\ContainerInterface;

interface IConfigValidatorContainer extends ContainerInterface
{
	public function add(string $name, callable $callback): void;

	/**
	 * @return string[]
	 */
	public function all(): array;
}
