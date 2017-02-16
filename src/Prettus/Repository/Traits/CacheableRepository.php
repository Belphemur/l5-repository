<?php

namespace Prettus\Repository\Traits;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Http\Request;
use Prettus\Repository\Helpers\CacheKeys;

/**
 * Class CacheableRepository
 *
 * @package Prettus\Repository\Traits
 */
trait CacheableRepository
{


    /**
     * @var bool
     */
    protected $cacheSkip = false;
    /**
     * @var CacheRepository
     */
    protected $cacheRepository = null;

    /**
     * Set Cache Repository
     *
     * @param CacheRepository $repository
     *
     * @return $this
     */
    public function setCacheRepository(CacheRepository $repository)
    {
        $this->cacheRepository = $repository;

        return $this;
    }

    /**
     * Return instance of Cache Repository
     *
     * @return CacheRepository
     */
    public function getCacheRepository()
    {
        if (is_null($this->cacheRepository)) {
            $this->cacheRepository = app(config('repository.cache.repository', 'cache'));
        }

        return $this->cacheRepository;
    }

    /**
     * Skip Cache
     *
     * @param bool $status
     *
     * @return $this
     */
    public function skipCache($status = true)
    {
        $this->cacheSkip = $status;

        return $this;
    }

    /**
     * @return bool
     */
    public function isSkippedCache()
    {
        $skipped        = isset($this->cacheSkip) ? $this->cacheSkip : false;
        $request        = app(Request::class);
        $skipCacheParam = config('repository.cache.params.skipCache', 'skipCache');

        if ($request->has($skipCacheParam) && $request->get($skipCacheParam)) {
            $skipped = true;
        }

        return $skipped;
    }

    /**
     * @param $method
     *
     * @return bool
     */
    protected function allowedCache($method)
    {
        $cacheEnabled = config('repository.cache.enabled', true);

        if (!$cacheEnabled) {
            return false;
        }

        $cacheOnly   = isset($this->cacheOnly) ? $this->cacheOnly  : config('repository.cache.allowed.only', null);
        $cacheExcept = isset($this->cacheExcept) ? $this->cacheExcept : config('repository.cache.allowed.except', null);


        if (is_null($cacheOnly) && is_null($cacheExcept)) {
            return true;
        }

        if (is_array($cacheOnly)) {
            return in_array($method, $cacheOnly);
        }

        if (is_array($cacheExcept)) {
            return !in_array($method, $cacheExcept);
        }


        return false;
    }

    /**
     * Get Cache key for the method
     *
     * @param $method
     * @param $args
     *
     * @return string
     */
    public function getCacheKey($method, $args = null)
    {
        /**
         * @var $request Request
         */
        $request       = app(Request::class);
        $args          = serialize($args);
        $paramsRequest = serialize($request->query());
        $key           = sprintf('%s@%s[%s]', get_called_class(), $method, md5($args . $paramsRequest));

        return $key;

    }

    /**
     * Get cache minutes
     *
     * @return int
     */
    public function getCacheMinutes()
    {
        $cacheMinutes = isset($this->cacheMinutes)
            ? $this->cacheMinutes
            : config('repository.cache.minutes', 30);

        return $cacheMinutes;
    }

    /**
     * Retrieve all data of repository
     *
     * @param array $columns
     *
     * @return mixed
     */
    public function all($columns = ['*'])
    {
        return $this->cacheRequest(function ($columns = ['*']) {
            return parent::all($columns);
        }, $columns);

    }

    /**
     * Retrieve all data of repository, paginated
     *
     * @param null  $limit
     * @param array $columns
     *
     * @return mixed
     */
    public function paginate($limit = null, $columns = ['*'])
    {
        return $this->cacheRequest(function ($limit = null, $columns = ['*']) {
            return parent::paginate($limit, $columns);
        }, $limit, $columns);

    }

    /**
     * Find data by id
     *
     * @param       $id
     * @param array $columns
     *
     * @return mixed
     */
    public function find($id, $columns = ['*'])
    {
        return $this->cacheRequest(function ($id, array $columns) {
            return parent::find($id, $columns);
        }, $id, $columns);
    }

    /**
     * Find data by field and value
     *
     * @param       $field
     * @param       $value
     * @param array $columns
     *
     * @return mixed
     */
    public function findByField($field, $value = null, $columns = ['*'])
    {
        return $this->cacheRequest(function ($field, $value, array $columns) {
            return parent::findByField($field, $value, $columns);
        }, $field, $value, $columns);
    }

    /**
     * Find data by multiple fields
     *
     * @param array $where
     * @param array $columns
     *
     * @return mixed
     */
    public function findWhere(array $where, $columns = ['*'])
    {
        return $this->cacheRequest(function (array $where, array $columns) {
            return parent::findWhere($where, $columns);
        }, $where, $columns);
    }


    /**
     * Used to manually cache a request. Useful when using methods of Eloquent and not the one of the Repository
     * interface
     *
     * @param \Closure $closure         Closure containing the function to execute with the result to cache
     * @param array    ...$functionArgs the argument of the closure
     *
     * @return mixed
     */
    private function cacheRequest(\Closure $closure, ...$functionArgs)
    {
        $method = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3)[1]['function'];

        $this->applyCriteria();
        $this->applyScope();

        $resultClosure = function () use ($closure, $functionArgs) {

            return call_user_func_array($closure, $functionArgs);
        };

        if ($this->isSkippedCache() || !$this->allowedCache($method)) {
            return $resultClosure();
        }

        $key     = $this->getCacheKey($method, $functionArgs);
        $minutes = $this->getCacheMinutes();

        if (!($result = $this->getCacheRepository()->get($key))) {
            $result = $resultClosure();

            $this->getCacheRepository()->put($key, $result, $minutes);
            CacheKeys::putKey(get_called_class(), $key);
        }

        $this->resetModel();

        return $result;
    }

}
