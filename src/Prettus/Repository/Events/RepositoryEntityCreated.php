<?php
namespace Prettus\Repository\Events;

/**
 * Class RepositoryEntityCreated
 * @package Prettus\Repository\Events
 */
class RepositoryEntityCreated extends RepositoryEventModelBase
{
    /**
     * @var string
     */
    protected $action = "created";
}
