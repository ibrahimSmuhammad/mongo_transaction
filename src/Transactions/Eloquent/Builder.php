<?php namespace Hema\MongoTransaction\Transactions\Eloquent;

use Hema\MongoTransaction\Transactions\Transaction;
use Hema\MongoTransaction\Transactions\Query\Builder as QueryBuilder;
use Illuminate\Database\Eloquent\Model;
use Jenssegers\Mongodb\Eloquent\Builder as EloquentBuilder;

class Builder extends EloquentBuilder
{
    /**
     * Insert a new record into the database.
     *
     * @param array $values
     * @return bool
     */
    public function insert(array $values)
    {
        // Since every insert gets treated like a batch insert, we will have to detect
        // if the user is inserting a single document or an array of documents.
        $session = Transaction::$session;
        $batch = true;

        foreach ($values as $value) {
            // As soon as we find a value that is not an array we assume the user is
            // inserting a single document.
            if (!is_array($value)) {
                $batch = false;
                break;
            }
        }

        if ($batch !== false) {
            $values = [$values];
        }
        // Batch insert
        // check if transaction session Started or Not
        if ($session) {
            $result = $this->toBase()->collection->insertMany($values, ['session' => $session]);
            return (1 == (int)$result->isAcknowledged());
        }

        $result = $this->toBase()->collection->insertMany($values);
        return (1 == (int)$result->isAcknowledged());
    }

    /**
     * trash a record in the database (soft delete).
     * soft delete
     * @return mixed
     */
    public function delete()
    {
        $values = ['$currentDate' => ['deleted_at' => true]];
        return $this->performUpdate($values);
    }

    /**
     * remove a record from the database.
     *
     * @return int
     */
    public function forceDelete()
    {
        $session = Transaction::$session;
        $keys = $this->toBase()->compileWheres();
        $flatten_id;
        foreach ($keys['$and'] as $k => $v){
            if ($k == '_id'){
                $flatten_id[$k] = $v;
                $wheres = array_flatten($flatten_id,1);
            }
        }
        // check transaction session
        if ($session) {
            $result = $this->toBase()->collection->DeleteMany($wheres, ['session' => $session]);
            if (1 == (int)$result->isAcknowledged()) {
                return $result->getDeletedCount();
            }
            return 0;
        }
        // end check transaction session

        $result = $this->toBase()->collection->DeleteMany($wheres);
        if (1 == (int)$result->isAcknowledged()) {
            return $result->getDeletedCount();
        }

        return 0;
    }

    /**
     * restore a record from the database.
     *
     * @return int
     */
    public function restore()
    {
        $values = ['$set' => ['deleted_at' => null]];
        return $this->performUpdate($values);
    }

    /**
     * Get a base query builder instance.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function toBase()
    {
        return $this->applyScopes()->getQuery();
    }

    /**
     * Perform an update query.
     *
     * @param array $query
     * @param array $options
     * @return int
     */
    protected function performUpdate($query, array $options = [])
    {
        // Update multiple items by default.
        if (!array_key_exists('multiple', $options)) {
            $options['multiple'] = true;
        }
        $session = Transaction::$session;
        if ($session) {
            $options['session'] = $session;
        }
        $keys = $this->toBase()->compileWheres();
        $flatten_id;
        foreach ($keys['$and'] as $k => $v){
            if ($k == '_id'){
                $flatten_id[$k] = $v;
                $wheres = array_flatten($flatten_id,1);
            }
        }

        $result = $this->toBase()->collection->UpdateMany($wheres, $query, $options);
        if (1 == (int)$result->isAcknowledged()) {
            return $result->getModifiedCount() ? $result->getModifiedCount() : $result->getUpsertedCount();
        }

        return 0;
    }

    /**
     * Insert a new record and get the value of the primary key.
     *
     * @param  array   $values
     * @param  string  $sequence
     * @return int
     */
    public function insertGetId(array $values, $sequence = null)
    {
        // Since every insert gets treated like a batch insert, we will have to detect
        // if the user is inserting a single document or an array of documents.
        $session = Transaction::$session;
        $batch = true;

        foreach ($values as $value) {
            // As soon as we find a value that is not an array we assume the user is
            // inserting a single document.
            if (!is_array($value)) {
                $batch = false;
                break;
            }
        }

        if ($batch !== false) {
            $values = [$values];
        }

        // Batch insert
        // check if transaction session Started or Not
        if ($session) {
            $result = $this->toBase()->collection->insertOne($values, ['session' => $session]);
        }else {
            $result = $this->toBase()->collection->insertOne($values);
        }
        if (1 == (int) $result->isAcknowledged()) {
            if (is_null($sequence)) {
                $sequence = '_id';
            }

            // Return id
            return $sequence == '_id' ? $result->getInsertedId() : $values[$sequence];
        }
    }


}
