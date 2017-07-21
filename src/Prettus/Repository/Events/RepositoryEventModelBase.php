<?php
namespace Prettus\Repository\Events;

use Illuminate\Database\Eloquent\Model;
use Prettus\Repository\Contracts\RepositoryInterface;

/**
 * Class RepositoryEventBase
 * @package Prettus\Repository\Events
 */
abstract class RepositoryEventModelBase extends RepositoryEventBase
{
    /**
     * @var Model
     */
    protected $model;

    /**
     * @param RepositoryInterface $repository
     * @param Model               $model
     */
    public function __construct(RepositoryInterface $repository, Model $model)
    {
        parent::__construct($repository);
        $this->model = $model;
    }

    /**
     * @return Model
     */
    public function getModel()
    {
        return $this->model;
    }
}
