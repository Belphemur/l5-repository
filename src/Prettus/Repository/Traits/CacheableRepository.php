<?php

namespace Prettus\Repository\Traits;

use Exception;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Http\Request;
use Prettus\Repository\Contracts\CriteriaInterface;
use Prettus\Repository\Helpers\CacheKeys;
use ReflectionObject;

/**
 * Class CacheableRepository
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
        $skipped = isset($this->cacheSkip) ? $this->cacheSkip : false;
        $request = app(Request::class);
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

        $cacheOnly = isset($this->cacheOnly) ? $this->cacheOnly : config('repository.cache.allowed.only', null);
        $cacheExcept = isset($this->cacheExcept) ? $this->cacheExcept : config('repository.cache.allowed.except', null);

        if (is_array($cacheOnly)) {
            return in_array($method, $cacheOnly);
        }

        if (is_array($cacheExcept)) {
            return !in_array($method, $cacheExcept);
        }

        if (is_null($cacheOnly) && is_null($cacheExcept)) {
            return true;
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
        $key           = sprintf('%s@%s-%s', get_called_class(), $method, md5($args . $paramsRequest));

        CacheKeys::putKey(get_called_class(), $key);

        return $key;

    }

    /**
     * Serialize the criteria making sure the Closures are taken care of.
     *
     * @return string
     */
    protected function serializeCriteria()
    {
        try {
            return serialize($this->getCriteria());
        } catch (Exception $e) {
            return serialize($this->getCriteria()->map(function ($criterion) {
                return $this->serializeCriterion($criterion);
            }));
        }
    }

    /**
     * Serialize single criterion with customized serialization of Closures.
     *
     * @param  \Prettus\Repository\Contracts\CriteriaInterface $criterion
     * @return \Prettus\Repository\Contracts\CriteriaInterface|array
     *
     * @throws \Exception
     */
    protected function serializeCriterion($criterion)
    {
        try {
            serialize($criterion);

            return $criterion;
        } catch (Exception $e) {
            // We want to take care of the closure serialization errors,
            // other than that we will simply re-throw the exception.
            if ($e->getMessage() !== "Serialization of 'Closure' is not allowed") {
                throw $e;
            }

            $r = new ReflectionObject($criterion);

            return [
                'hash' => md5((string) $r),
                'properties' => $r->getProperties(),
            ];
        }
    }

    /**
     * Get cache minutes
     *
     * @return int
     */
    public function getCacheMinutes()
    {
        $cacheMinutes = isset($this->cacheMinutes) ? $this->cacheMinutes : config('repository.cache.minutes', 30);

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
        if (!$this->allowedCache('all') || $this->isSkippedCache()) {
            return parent::all($columns);
        }

        $key = $this->getCacheKey('all', func_get_args());
        $minutes = $this->getCacheMinutes();
        $value = $this->getCacheRepository()->remember($key, $minutes, function () use ($columns) {
            return parent::all($columns);
        });

        return $value;
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
        if (!$this->allowedCache('paginate') || $this->isSkippedCache()) {
            return parent::paginate($limit, $columns);
        }

        $key = $this->getCacheKey('paginate', func_get_args());

        $minutes = $this->getCacheMinutes();
        $value = $this->getCacheRepository()->remember($key, $minutes, function () use ($limit, $columns) {
            return parent::paginate($limit, $columns);
        });

        return $value;
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
        if (!$this->allowedCache('find') || $this->isSkippedCache()) {
            return parent::find($id, $columns);
        }

        $key = $this->getCacheKey('find', func_get_args());
        $minutes = $this->getCacheMinutes();
        $value = $this->getCacheRepository()->remember($key, $minutes, function () use ($id, $columns) {
            return parent::find($id, $columns);
        });

        return $value;
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
        if (!$this->allowedCache('findByField') || $this->isSkippedCache()) {
            return parent::findByField($field, $value, $columns);
        }

        $key = $this->getCacheKey('findByField', func_get_args());
        $minutes = $this->getCacheMinutes();
        $value = $this->getCacheRepository()->remember($key, $minutes, function () use ($field, $value, $columns) {
            return parent::findByField($field, $value, $columns);
        });

        return $value;
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
        if (!$this->allowedCache('findWhere') || $this->isSkippedCache()) {
            return parent::findWhere($where, $columns);
        }

        $key = $this->getCacheKey('findWhere', func_get_args());
        $minutes = $this->getCacheMinutes();
        $value = $this->getCacheRepository()->remember($key, $minutes, function () use ($where, $columns) {
            return parent::findWhere($where, $columns);
        });

        return $value;
    }

    /**
     * Find data by Criteria
     *
     * @param CriteriaInterface $criteria
     *
     * @return mixed
     */
    public function getByCriteria(CriteriaInterface $criteria)
    {
        if (!$this->allowedCache('getByCriteria') || $this->isSkippedCache()) {
            return parent::getByCriteria($criteria);
        }

        $key = $this->getCacheKey('getByCriteria', func_get_args());
        $minutes = $this->getCacheMinutes();
        $value = $this->getCacheRepository()->remember($key, $minutes, function () use ($criteria) {
            return parent::getByCriteria($criteria);
        });

        return $value;
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
        if ($this->isSkippedCache()) {
            $this->applyCriteria();
            $this->applyScope();

            return call_user_func_array($closure, $functionArgs);
        }

        $key     = $this->getCacheKey(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3)[1]['function'], $functionArgs);
        $minutes = $this->getCacheMinutes();
        $value   = $this->getCacheRepository()->remember($key, $minutes, function () use ($closure, $functionArgs) {
            $this->applyCriteria();
            $this->applyScope();

            return call_user_func_array($closure, $functionArgs);
        });

        $this->resetModel();

        return $value;
    }

}
