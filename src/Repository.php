<?php

namespace SimpleRepository;

use SimpleRepository\Concerns\Safetyable;

/**
 * @template T
 */
abstract class Repository
{
    use Safetyable;

    /**
     * @return T
     */
    abstract public function getDataSource();
}
