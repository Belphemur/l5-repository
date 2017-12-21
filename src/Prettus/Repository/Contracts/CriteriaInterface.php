<?php
namespace Prettus\Repository\Contracts;

use Illuminate\Database\Eloquent\Builder;

/**
 * Interface CriteriaInterface
 * @package Prettus\Repository\Contracts
 */
interface CriteriaInterface
{
    /**
     * Apply criteria in query repository
     *
     * @param                     $model
     * @param RepositoryInterface $repository
     *
     * @return mixed
     */
    public function apply(Builder $model, RepositoryInterface $repository);
}
