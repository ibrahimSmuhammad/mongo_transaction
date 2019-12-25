<?php

namespace Hema\MongoTransaction\Transactions;
use DB;

class Transaction
{
    public static $session;
    /**
     * Start transaction.
     * @return void
     */
    public static function start(){
        $branch = config('database.default');
        $mongoClient = DB::connection($branch)->getMongoClient();
        self::$session = $mongoClient->startSession();
        return self::$session->startTransaction();
    }

    /**
     * Commit transaction.
     * @return void
     */
    public static function commit(){
        return self::$session->commitTransaction();
    }

    /**
     * Rollback transaction.
     * @return void
     */
    public static function rollback(){
        return self::$session->abortTransaction();
    }
}
