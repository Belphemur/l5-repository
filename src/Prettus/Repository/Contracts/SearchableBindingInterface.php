<?php
/**
 * Created by PhpStorm.
 * User: aaflalo
 * Date: 10/02/17
 * Time: 4:24 PM
 */

namespace Prettus\Repository\Contracts;


interface SearchableBindingInterface
{
    /**
     * Array of closure to bind a searchable field value to its value in the database
     *
     * @return \Closure[]
     */
    public function getFieldBindings();
}