<?php

namespace SimpleRepository;

use SimpleRepository\Concerns\Safetyable;

/**
 * @template T
 *
 * @deprecated When creating a repository class implement \SimpleRepository\Contracts\Repository interface instead.
 */
abstract class Repository
{
    use Safetyable;

    /**
     * @return T
     */
    abstract public function getDataSource();
}
