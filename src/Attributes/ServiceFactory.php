<?php

namespace SimpleRepository\Attributes;

use Attribute;
use Illuminate\Container\Container;

#[Attribute(Attribute::TARGET_PROPERTY)]
class ServiceFactory
{
    public function __construct(
        protected string $serviceName,
    ) {
    }

    public function getService()
    {
        return Container::getInstance()->make($this->serviceName);
    }
}
