<?php
/**
 * Created by PhpStorm.
 * User: aaflalo
 * Date: 10/02/17
 * Time: 3:04 PM
 */

namespace Prettus\Repository\Traits;


/**
 * Class TransactionHelperTrait
 *
 * Contains the methods for managing transactions
 *
 * @package Cumulus\Repositories
 */
trait TransactionHelper
{

    /**
     * Execute operation in transaction
     *
     * @param \Closure $callable
     *
     * @return mixed
     */
    public function inTransaction(\Closure $callable)
    {
        return \DB::transaction($callable);
    }

    /**
     * Start a transaction
     */
    public function beginTransaction()
    {
        \DB::beginTransaction();
    }

    /**
     * Roll back a transaction
     */
    public function rollbackTransaction()
    {
        \DB::rollBack();
    }

    /**
     * Commit a transaction
     */
    public function commitTransaction()
    {
        \DB::commit();
    }

}