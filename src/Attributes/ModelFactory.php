<?php

namespace SimpleRepository\Attributes;

use Attribute;
use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Model;
use SimpleRepository\RepositoryMakeModelException;

#[Attribute(Attribute::TARGET_PROPERTY)]
class ModelFactory
{
    public function __construct(
        protected string $modelName,
    ) {
    }

    /**
     * Get a new Eloquent model instance.
     *
     * @throws \SimpleRepository\RepositoryMakeModelException
     */
    public function getModel(array $attributes = []): Model
    {
        $model = Container::getInstance()->make($this->modelName, [
            'attributes' => $attributes,
        ]);

        if (! $model instanceof Model) {
            throw new RepositoryMakeModelException('Class '.$this->modelName.' must be an instance of '.Model::class.'.');
        }

        return $model;
    }
}
