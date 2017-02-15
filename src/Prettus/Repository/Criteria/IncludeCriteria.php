<?php
/**
 * Created by PhpStorm.
 * User: aaflalo
 * Date: 15/02/17
 * Time: 11:26 AM
 */

namespace Prettus\Repository\Criteria;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Prettus\Repository\Contracts\CriteriaInterface;
use Prettus\Repository\Contracts\RepositoryInterface;

class IncludeCriteria implements CriteriaInterface
{
    /**
     * @var \Illuminate\Http\Request
     */
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }
    /**
     * Apply criteria in query repository
     *
     * @param                     $model
     * @param RepositoryInterface $repository
     *
     * @return mixed
     */
    public function apply($model, RepositoryInterface $repository)
    {
        $with         = $this->request->get(config('repository.criteria.params.with', 'with'), null);
        $fieldIncludable  = $repository->getFieldIncludable();

        if ($with && in_array($with, $fieldIncludable)) {
            $model = $this->processWith($model, $with);
        }

        return $model;
    }

    /**
     * Process includes
     *
     * @param Builder|Model $model
     * @param string        $with
     *
     * @return Builder|Model
     */
    protected function processWith($model, string $with)
    {
        $with  = explode(';', $with);
        $model = $model->with($with);

        return $model;
    }
}