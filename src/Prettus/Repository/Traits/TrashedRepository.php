<?php
/**
 * Created by PhpStorm.
 * User: aaflalo
 * Date: 10/02/17
 * Time: 3:02 PM
 */

namespace Prettus\Repository\Traits;


trait TrashedRepository
{
    /**
     * @var bool
     */
    private $trashed = false;

    public function setWithTrashed(bool $trashed)
    {
        $this->trashed = $trashed;
        $this->makeModel();

        return $this;
    }

    public function makeModel()
    {
        $model = parent::makeModel();
        if ($this->trashed) {
            $model = $model->withTrashed();
        }

        return $this->model = $model;
    }
}