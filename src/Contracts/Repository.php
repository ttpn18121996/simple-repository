<?php

namespace SimpleRepository\Contracts;

/**
 * @template T
 */
interface Repository
{
    /**
     * @return T
     */
    public function getDataSource();
}
