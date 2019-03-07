<?php
/**
 * Created by PhpStorm.
 * User: aaflalo
 * Date: 19-03-07
 * Time: 15:29
 */

namespace Prettus\Repository\Contracts\Criteria\Request;

use Prettus\Repository\Contracts\CriteriaInterface;

interface ValidatedRequestCriteria extends CriteriaInterface
{

    /**
     * Rules to use to validate the data used by the criteria
     *
     * @return string[]
     */
    public function validationRules(): array;

    /**
     * Message to be used to override the default validation
     *
     * @return string[]
     */
    public function validationMessages(): array;
}
