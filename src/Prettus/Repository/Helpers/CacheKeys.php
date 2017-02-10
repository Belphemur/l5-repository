<?php

namespace Prettus\Repository\Helpers;

/**
 * Class CacheKeys
 *
 * @package Prettus\Repository\Helpers
 */
class CacheKeys
{

    /**
     * @param $group
     * @param $key
     *
     * @return void
     */
    public static function putKey($group, $key)
    {
        $repoKey = self::getGroupKey($group);

        $keys   = self::getKeys($group);
        $keys[] = $key;
        \Cache::put($repoKey, serialize($keys), config('repository.cache.minutes'));
    }

    /**
     * @param $group
     *
     * @return array|mixed
     */
    public static function getKeys($group): array
    {
        $repoKey = self::getGroupKey($group);

        if (!$result = \Cache::get($repoKey)) {
            return [];
        }

        return unserialize($result);
    }

    /**
     * Clean all the keys of the group and the group key
     *
     * @param $group
     */
    public static function cleanKeys($group)
    {
        $repoKey = self::getGroupKey($group);
        foreach (self::getKeys($group) as $key) {
            \Cache::forget($key);
        }
        \Cache::forget($repoKey);
    }

    /**
     * @param $method
     * @param $parameters
     *
     * @return mixed
     */
    public static function __callStatic($method, $parameters)
    {
        $instance = new static;

        return call_user_func_array([
            $instance,
            $method,
        ], $parameters);
    }

    /**
     * @param $group
     *
     * @return string
     */
    private static function getGroupKey($group): string
    {
        return 'repository/keys/' . $group;
    }

    /**
     * @param $method
     * @param $parameters
     *
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        $instance = new static;

        return call_user_func_array([
            $instance,
            $method,
        ], $parameters);
    }
}
