<?php

namespace SimpleRepository\Traits;

use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use SimpleRepository\ServiceNotFoundException;
use Throwable;

trait Safetyable
{
    /**
     * Safely execute database interactions using transaction.
     *
     * @param  \Closure  $callback
     * @param  string  $titleError
     * @return mixed
     */
    protected function handleSafely(Closure $callback, string $titleError = 'Process')
    {
        DB::beginTransaction();

        try {
            $result = call_user_func($callback);

            DB::commit();

            return $result;
        } catch (ServiceNotFoundException $e) {
            DB::rollBack();

            Log::error("{$titleError}: Incorrect service class name or service class does not exist. Initialize the service manually to ensure that it exists.");

            return null;
        } catch (Throwable $e) {
            DB::rollBack();

            Log::error("{$titleError}: {$e->getMessage()}");

            return null;
        }
    }
}
