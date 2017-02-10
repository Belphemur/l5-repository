<?php
/**
 * Created by PhpStorm.
 * User: aaflalo
 * Date: 10/02/17
 * Time: 3:14 PM
 */

namespace Prettus\Repository\Contracts;


interface TransactionableInterface
{

    /**
     * Execute operation in transaction
     *
     * @param \Closure $callable
     * @return mixed
     */
    public function inTransaction(\Closure $callable);

    /**
     * Start a transaction
     */
    public function beginTransaction();

    /**
     * Roll back a transaction
     */
    public function rollbackTransaction();

    /**
     * Commit a transaction
     */
    public function commitTransaction();

}