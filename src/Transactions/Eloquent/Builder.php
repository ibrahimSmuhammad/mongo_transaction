<?php namespace Hema\MongoTransaction\Transactions\Eloquent;

use Hema\MongoTransaction\Transactions\Transaction;
use Hema\MongoTransaction\Transactions\Query\Builder as QueryBuilder;
use Jenssegers\Mongodb\Eloquent\Builder as EloquentBuilder;

class Builder extends EloquentBuilder
{
    /**
     * trash a record in the database (soft delete).
     *
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

}
