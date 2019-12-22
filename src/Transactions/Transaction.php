<?php

namespace Hema\MongoTransaction\Transactions;
use Hema\MongoTransaction\Transactions\Builder;

class Transaction extends Builder
{

    /**
     * Start transaction.
     * @return void
     */
    public static function start(){
        return self::startTransaction();
    }

    /**
     * Commit transaction.
     * @return void
     */
    public static function commit(){
        return self::commitTransaction();
    }

    /**
     * Rollback transaction.
     * @return void
     */
    public static function rollback(){
        return self::rollbackTransaction();
    }

}
