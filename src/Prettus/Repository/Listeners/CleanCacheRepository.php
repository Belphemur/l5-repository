<?php

namespace Prettus\Repository\Listeners;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\Log;
use Prettus\Repository\Events\RepositoryCleanEvent;
use Prettus\Repository\Events\RepositoryEventBase;
use Prettus\Repository\Helpers\CacheKeys;

/**
 * Class CleanCacheRepository
 *
 * @package Prettus\Repository\Listeners
 */
class CleanCacheRepository
{

    /**
     * @var CacheRepository
     */
    protected $cache = null;


    /**
     *
     */
    public function __construct()
    {
        $this->cache = app(config('repository.cache.repository', 'cache'));
    }

    /**
     * @param RepositoryEventBase $event
     */
    public function handle(RepositoryEventBase $event)
    {
        try {
            $cleanEnabled = config("repository.cache.clean.enabled", true);

            if ($cleanEnabled) {
                $repository = $event->getRepository();
                $action     = $event->getAction();

                if ($action == RepositoryCleanEvent::ACTION
                    || config("repository.cache.clean.on.{$action}", true)
                ) {
                    CacheKeys::cleanKeys(get_class($repository));
                }
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
    }
}
