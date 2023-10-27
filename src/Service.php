<?php

namespace SimpleRepository;

use Exception;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

abstract class Service
{
    protected array $authUsers;

    /**
     * Get the authenticated user for the service.
     */
    public function authUser($guard = null)
    {
        $guard ??= Config::get('auth.defaults.guard');

        return Arr::get($this->authUsers, $guard);
    }

    /**
     * Set the authenticated user for the service.
     */
    public function useAuthUser($user, $guard = null)
    {
        $guard ??= Config::get('auth.defaults.guard');

        $this->authUsers[$guard] = $user;

        return $this;
    }

    /**
     * Get a new service instance.
     *
     * @throws \Exception
     */
    public function getService(string $serviceName)
    {
        $className = Str::of($serviceName)->studly();
        $service = '\\App\\Services\\'.$className->toString();

        if (class_exists($service)) {
            return Container::getInstance()->make($service);
        }

        throw new Exception($service.' does not exist.');
    }
}
