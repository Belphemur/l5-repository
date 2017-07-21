<?php

namespace Prettus\Repository\Events;

use Prettus\Repository\Contracts\RepositoryInterface;

/**
 * Class RepositoryEventBase
 *
 * @package Prettus\Repository\Events
 */
abstract class RepositoryEventBase
{
    /**
     * @var RepositoryInterface
     */
    protected $repository;

    /**
     * @var string
     */
    protected $action;

    /**
     * @param RepositoryInterface $repository
     */
    public function __construct(RepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @return RepositoryInterface
     */
    public function getRepository()
    {
        return $this->repository;
    }

    /**
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }
}
