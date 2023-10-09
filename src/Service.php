<?php

namespace SimpleRepository;

use Illuminate\Container\Container;
use Illuminate\Support\Str;

abstract class Service
{
    public function __get($property)
    {
        $className = Str::of($property)->studly();
        $service = '\\App\\Services\\'.$className->toString();

        if ($className->endsWith('Service') && class_exists($service)) {
            return Container::getInstance()->make($service);
        }

        return $this?->{$property} ?? null;
    }
}
