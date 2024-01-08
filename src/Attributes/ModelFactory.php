<?php

namespace SimpleRepository\Attributes;

use Attribute;
use Illuminate\Container\Container;

#[Attribute(Attribute::TARGET_PROPERTY)]
class ModelFactory
{
    public function __construct(
        protected string $modelName,
    ) {
    }

    public function getModel(array $attributes = [])
    {
        return Container::getInstance()->make($this->modelName, [
            'attributes' => $attributes,
        ]);
    }
}
