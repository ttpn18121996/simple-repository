<?php

namespace SimpleRepository;

use SimpleRepository\Concerns\Safetyable;

/**
 * @template T
 */
abstract class Repository
{
    use Safetyable;

    /** @var T */
    protected $dataSource;

    /**
     * @return T
     */
    abstract public function getDataSource();
}
