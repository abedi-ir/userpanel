<?php
namespace Jalno\Userpanel\Rules;

use Jalno\Userpanel\Contracts;
use Illuminate\Contracts\Validation\Rule;

class ConfigValidatorRule implements Rule
{
    protected Contracts\IConfigValidatorContainer $container;
    protected string $config;
    /** @property callable */
    protected $callback;

	public function __construct(Contracts\IConfigValidatorContainer $container, ?string $config = null)
	{
        $this->container = $container;
        if ($config) {
            $this->config = $config;
            $this->setCallBack($this->container->get($config));
        }
    }
    
    public function setCallBack(callable $callback)
    {
        $this->callback = $callback;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     * @param mixed  $value
     * 
     * @return bool
     */
    public function passes($attribute, $value)
    {
        if (!isset($value["name"]) or !isset($value["value"])) {
            return false;
        }
        return call_user_func($this->resolveCallBack($value["name"]), $value["value"], $value["name"]);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The :attribute is invalid.';
    }

    protected function resolveCallBack(string $config): callable
    {
        if ($this->callback) {
            return $this->callback;
        }

        return $this->container->has($config) ? $this->container->get($config) : fn($value) => false;
    }
}
