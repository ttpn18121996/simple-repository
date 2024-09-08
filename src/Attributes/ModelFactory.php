<?php

namespace SimpleRepository\Attributes;

use Attribute;
use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Model;
use SimpleRepository\RepositoryMakeModelException;

/**
 * @template TModel
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class ModelFactory
{
    /**
     * @param  class-string<TModel>
     */
    public function __construct(
        protected string $modelName,
    ) {
    }

    /**
     * Get a new Eloquent model instance.
     *
     * @return TModel
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
