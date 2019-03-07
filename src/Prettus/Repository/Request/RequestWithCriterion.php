<?php
/**
 * Created by PhpStorm.
 * User: aaflalo
 * Date: 19-03-07
 * Time: 15:38
 */

namespace Prettus\Repository\Request;

use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Illuminate\Foundation\Http\FormRequest;

abstract class RequestWithCriterion extends FormRequest implements \Prettus\Repository\Contracts\Request\RequestWithCriterion
{
    /**
     * Create the default validator instance.
     *
     * @param  \Illuminate\Contracts\Validation\Factory $factory
     *
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(ValidationFactory $factory)
    {
        $rules       = $this->container->call([$this, 'rules']);
        $messages    = $this->messages();
        $allRules    = $rules;
        $allMessages = $messages;

        foreach ($this->criterion() as $criteria) {
            $allRules    = array_merge($allRules, $criteria->validationRules());
            $allMessages = array_merge($allMessages, $criteria->validationMessages());
        }

        return $factory->make(
            $this->validationData(),
            $rules,
            $messages,
            $this->attributes()
        );
    }
}
