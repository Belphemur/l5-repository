<?php
namespace Prettus\Repository\Events;

/**
 * Class RepositoryEntityDeleted
 * @package Prettus\Repository\Events
 */
class RepositoryEntityDeleted extends RepositoryEventModelBase
{
    /**
     * @var string
     */
    protected $action = "deleted";
}
