<?php

namespace SimpleRepository;

use Exception;
use Illuminate\Container\Container;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use SimpleRepository\Traits\HasFilter;
use SimpleRepository\Traits\Safetyable;

abstract class Service
{
    use HasFilter, Safetyable;

    /**
     * List of authenticated users classified by guard.
     *
     * @var array
     */
    protected array $authUsers = [];

    /**
     * Get the authenticated user for the service.
     *
     * @param  string|null  $guard
     * @return \Illuminate\Contracts\Auth\Authenticatable
     */
    public function authUser(?string $guard = null)
    {
        $guard ??= Config::get('auth.defaults.guard');

        return Arr::get($this->authUsers, $guard);
    }

    /**
     * Set the authenticated user for the service.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  string|null  $guard
     * @return $this
     */
    public function useAuthUser($user, ?string $guard = null): static
    {
        $guard ??= Config::get('auth.defaults.guard');

        $this->authUsers[$guard] = $user;

        return $this;
    }

    /**
     * Get a new service instance.
     *
     * @param  string  $serviceName
     * @return mixed
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
