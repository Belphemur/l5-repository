<?php
namespace Prettus\Repository\Events;

/**
 * Class RepositoryEntityUpdated
 * @package Prettus\Repository\Events
 */
class RepositoryEntityUpdated extends RepositoryEventModelBase
{
    /**
     * @var string
     */
    protected $action = "updated";
}
