<?php
/**
 * Created by PhpStorm.
 * User: aaflalo
 * Date: 17-07-21
 * Time: 14:35
 */

namespace Prettus\Repository\Events;

class RepositoryCleanEvent extends RepositoryEventBase
{

    const ACTION = 'clean';
    protected $action = self::ACTION;
}
