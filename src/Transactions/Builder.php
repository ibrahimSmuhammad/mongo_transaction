<?php

namespace Hema\MongoTransaction\Transactions;

use Jenssegers\Mongodb\Connection;
use Jenssegers\Mongodb\Query\Builder as BaseBuilder;
use Jenssegers\Mongodb\Query\Processor;
use DB;
class Builder extends BaseBuilder
{
    protected static $session;

    /**
       * Insert a new record into the database.
       *
       * @param  array  $values
       * @return bool
       */
      public function insert(array $values)
      {
          // Since every insert gets treated like a batch insert, we will have to detect
          // if the user is inserting a single document or an array of documents.
          $batch = true;

          foreach ($values as $value) {
              // As soon as we find a value that is not an array we assume the user is
              // inserting a single document.
              if (! is_array($value)) {
                  $batch = false;
                  break;
              }
          }

          if (! $batch) {
              $values = [$values];
          }
          // Batch insert
          // check if transaction session Started or Not
          if (self::$session){
              $result = $this->collection->insertMany($values, ['session' => self::$session]);
              return (1 == (int) $result->isAcknowledged());
          }

          $result = $this->collection->insertMany($values);
          return (1 == (int) $result->isAcknowledged());
      }

    /**
     * Update a record in the database.
     *
     * @param  array  $values
     * @param  array  $options
     * @return int
     */
      public function update(array $values, array $options = [])
      {
          // Use $set as default operator.
          if (! starts_with(key($values), '$')) {
              $values = ['$set' => $values];
          }

          return $this->performUpdate($values, $options);
      }

    /**
     * Delete a record from the database.
     *
     * @param  mixed  $id
     * @return int
     */
    public function delete($id = null)
    {
        $wheres = $this->compileWheres();

        // check transaction session
        if (self::$session){
            $result = $this->collection->DeleteMany($wheres, ['session' => self::$session]);
            if (1 == (int) $result->isAcknowledged()) {
                return $result->getDeletedCount();
            }
            return 0;
        }
        // end check transaction session

        $result = $this->collection->DeleteMany($wheres);
        if (1 == (int) $result->isAcknowledged()) {
            return $result->getDeletedCount();
        }

        return 0;
    }

    /**
     * Increment a column's value by a given amount.
     *
     * @param  string  $column
     * @param  int     $amount
     * @param  array   $extra
     * @return int
     */
    public function increment($column, $amount = 1, array $extra = [], array $options = [])
    {
        $query = ['$inc' => [$column => $amount]];

        if (! empty($extra)) {
            $query['$set'] = $extra;
        }

        // Protect
        $this->where(function ($query) use ($column) {
            $query->where($column, 'exists', false);

            $query->orWhereNotNull($column);
        });

        return $this->performUpdate($query, $options);
    }

    /**
     * Decrement a column's value by a given amount.
     *
     * @param  string  $column
     * @param  int     $amount
     * @param  array   $extra
     * @return int
     */
    public function decrement($column, $amount = 1, array $extra = [], array $options = [])
    {
        return $this->increment($column, -1 * $amount, $extra, $options);
    }

    /**
     * Perform an update query.
     *
     * @param  array  $query
     * @param  array  $options
     * @return int
     */
    protected function performUpdate($query, array $options = [])
    {
        // Update multiple items by default.
        if (! array_key_exists('multiple', $options)) {
            $options['multiple'] = true;
        }

        $wheres = $this->compileWheres();
        // check transaction session
        if (self::$session){
            $result = $this->collection->UpdateMany($wheres, $query, $options, ['session', self::$session]);
            if (1 == (int) $result->isAcknowledged()) {
                return $result->getModifiedCount() ? $result->getModifiedCount() : $result->getUpsertedCount();
            }

            return 0;
        }
        // end check transaction session
        $result = $this->collection->UpdateMany($wheres, $query, $options);
        if (1 == (int) $result->isAcknowledged()) {
            return $result->getModifiedCount() ? $result->getModifiedCount() : $result->getUpsertedCount();
        }

        return 0;
    }



    /**
     * Start transaction.
     * @return void
     */
    public static function startTransaction(){
        $mongoClient = DB::connection('tuv_abudhabi_new')->getMongoClient();
        self::$session = $mongoClient->startSession();
        return self::$session->startTransaction();
    }

    /**
     * Commit transaction.
     * @return void
     */
    public static function commitTransaction(){
        return self::$session->commitTransaction();
    }

    /**
     * Rollback transaction.
     * @return void
     */
    public static function rollbackTransaction(){
        return self::$session->abortTransaction();
    }

}
