<?php
/**
 * Created by PhpStorm.
 * User: aaflalo
 * Date: 19-03-07
 * Time: 15:32
 */

namespace Prettus\Repository\Contracts\Request;

use Prettus\Repository\Contracts\Criteria\Request\ValidatedRequestCriteria;

interface RequestWithCriterion
{
    /**
     * The criteria to use in this request
     *
     * @return ValidatedRequestCriteria[]
     */
    public function criterion(): array;
}
