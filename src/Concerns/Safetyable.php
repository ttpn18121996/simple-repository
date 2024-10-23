<?php

namespace SimpleRepository\Concerns;

use Closure;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use SimpleRepository\ServiceNotFoundException;
use Throwable;

trait Safetyable
{
    /**
     * Safely execute database interactions using transaction.
     */
    protected function handleSafely(Closure $callback, string $titleError = 'Process')
    {
        $logChannel = Config::get('simple-repository.log_channel', 'stack');
        $result = null;

        DB::beginTransaction();

        try {
            $result = call_user_func($callback);

            DB::commit();
        } catch (ServiceNotFoundException $e) {
            DB::rollBack();

            Log::channel($logChannel)
                ->error("{$titleError}: Incorrect service class name or service class does not exist.
                Initialize the service manually to ensure that it exists.");
        } catch (Throwable $e) {
            DB::rollBack();

            Log::channel($logChannel)->error("{$titleError}: {$e->getMessage()}");
        }

        return $result;
    }
}
