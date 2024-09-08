<?php

namespace SimpleRepository\Attributes;

use Attribute;
use Illuminate\Container\Container;

/**
 * @template TService
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class ServiceFactory
{
    /**
     * @param  class-string<TService>
     */
    public function __construct(
        protected string $serviceName,
    ) {
    }

    /**
     * Create a new service instance.
     *
     * @return TService
     */
    public function getService()
    {
        return Container::getInstance()->make($this->serviceName);
    }
}
