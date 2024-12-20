<?php

namespace SimpleRepository;

use ErrorException;
use Illuminate\Container\Container;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use ReflectionClass;
use SimpleRepository\Attributes\ModelFactory;
use SimpleRepository\Attributes\ServiceFactory;
use SimpleRepository\Concerns\HasFilter;
use SimpleRepository\Concerns\Safetyable;

abstract class Service
{
    use HasFilter, Safetyable;

    /**
     * List of authenticated users classified by guard.
     */
    protected array $authUsers = [];

    /**
     * Get the authenticated user for the service.
     */
    public function authUser(?string $guard = null): ?Authenticatable
    {
        $guard ??= Config::get('auth.defaults.guard');

        return Arr::get($this->authUsers, $guard);
    }

    /**
     * Set the authenticated user for the service.
     */
    public function useAuthUser(Authenticatable $user, ?string $guard = null): static
    {
        $guard ??= Config::get('auth.defaults.guard');

        $this->authUsers[$guard] = $user;

        return $this;
    }

    /**
     * Get a new service instance.
     *
     * @throws \SimpleRepository\ServiceNotFoundException
     */
    public function getService(string $serviceName)
    {
        $reflection = new ReflectionClass($this);

        foreach ($reflection->getProperties() as $property) {
            $propertyName = $property->getName();

            foreach ($property->getAttributes() as $propertysAttribute) {
                if (
                    $propertysAttribute->getName() == ServiceFactory::class
                    && $serviceName == $propertyName
                ) {
                    $serviceFactoryReflection = new ReflectionClass($propertysAttribute->getName());

                    /** @var \SimpleRepository\Attributes\ServiceFactory */
                    $serviceFactory = $serviceFactoryReflection->newInstance(...$propertysAttribute->getArguments());

                    return $serviceFactory->getService();
                }
            }
        }

        return $this->findService($serviceName);
    }

    /**
     * Search for services whose namespace is "\App\Services" and resolve that.
     *
     * @throws \SimpleRepository\ServiceNotFoundException
     */
    public function findService(string $serviceName)
    {
        $className = Str::of($serviceName)->studly();
        $service = '\\App\\Services\\'.$className->toString();

        if (class_exists($service)) {
            return Container::getInstance()->make($service);
        }

        throw new ServiceNotFoundException($service.' does not exist.');
    }

    /**
     * Get the instance of the model that was instantiated from the properties whose attribute is the model factory.
     */
    public function getModel(?string $name = null, array $attributes = [])
    {
        $name ??= 'model';

        if (! property_exists($this, $name)) {
            throw new ErrorException('Undefined property: '.get_class($this).'::$'.$name);
        }

        if (! is_null($this->{$name})) {
            return $this->{$name};
        }

        $reflectionClass = new ReflectionClass($this);
        $reflectionProperty = $reflectionClass->getProperty($name);

        foreach ($reflectionProperty->getAttributes() as $propertysAttribute) {
            $attributeName = $propertysAttribute->getName();
            if ($attributeName == ModelFactory::class) {
                $modelFactoryReflection = new ReflectionClass($attributeName);

                /** @var \SimpleRepository\Attributes\ModelFactory */
                $modelFactory = $modelFactoryReflection->newInstance(...$propertysAttribute->getArguments());

                $this->{$name} = $modelFactory->getModel($attributes);
            }
        }

        return $this->{$name} ?? null;
    }
}
