<?php
namespace Hema\MongoTransaction\Transactions\Query;

use Hema\MongoTransaction\Transactions\Transaction;
use Jenssegers\Mongodb\Query\Builder as BaseBuilder;
use MongoDB\BSON\Regex;
use MongoDB\BSON\UTCDateTime;

class Builder extends BaseBuilder
{
    public $collection;

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

        if (!$batch) {
            $values = [$values];
        }
        // Batch insert
        // check if transaction session Started or Not
        if ($session) {
            $result = $this->collection->insertMany($values, ['session' => $session]);
            return (1 == (int)$result->isAcknowledged());
        }

        $result = $this->collection->insertMany($values);
        return (1 == (int)$result->isAcknowledged());
    }

    /**
     * Update a record in the database.
     *
     * @param array $values
     * @param array $options
     * @return int
     */
    public function update(array $values, array $options = [])
    {
        // Use $set as default operator.
        if (!starts_with(key($values), '$')) {
            $values = ['$set' => $values];
        }

        return $this->performUpdate($values, $options);
    }

    /**
     * trash a record in the database.
     *
     * @return int
     */
    public function trash($id = null)
    {
        // Use $set as default operator.
        $session = Transaction::$session;
        $values = ['$currentDate' => ['deleted_at' => true]];
        if ($session) {
            $options = ['session' => $session];
        }

        return $this->performUpdate($values, $options);

    }

    /**
     * restore a record from the database.
     *
     * @return int
     */
    public function restore()
    {
        $values = ['$currentDate' => ['deleted_at' => false]];
         return $this->performUpdate($values);
    }

    /**
     * Delete a record from the database.
     *
     * @param mixed $id
     * @return int
     */
    public function remove($id = null)
    {
        $session = Transaction::$session;
        $wheres = $this->compileWheres();
        // check transaction session
        if ($session) {
            $result = $this->collection->DeleteMany($wheres, ['session' => $session]);
            if (1 == (int)$result->isAcknowledged()) {
                return $result->getDeletedCount();
            }
            return 0;
        }
        // end check transaction session

        $result = $this->collection->DeleteMany($wheres);
        if (1 == (int)$result->isAcknowledged()) {
            return $result->getDeletedCount();
        }

        return 0;
    }

    /**
     * Increment a column's value by a given amount.
     *
     * @param string $column
     * @param int $amount
     * @param array $extra
     * @return int
     */
    public function increment($column, $amount = 1, array $extra = [], array $options = [])
    {
        $query = ['$inc' => [$column => $amount]];

        if (!empty($extra)) {
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
     * @param string $column
     * @param int $amount
     * @param array $extra
     * @return int
     */
    public function decrement($column, $amount = 1, array $extra = [], array $options = [])
    {
        return $this->increment($column, -1 * $amount, $extra, $options);
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
        if ($session){
            $options['session'] = $session;
        }
        $wheres = $this->compileWheres();

        $result = $this->collection->UpdateMany($wheres, $query, $options);
        if (1 == (int)$result->isAcknowledged()) {
            return $result->getModifiedCount() ? $result->getModifiedCount() : $result->getUpsertedCount();
        }

        return 0;
    }

    public function compileWheres()
    {
        // The wheres to compile.
        $wheres = $this->wheres ?: [];

        // We will add all compiled wheres to this array.
        $compiled = [];

        foreach ($wheres as $i => &$where) {
            // Make sure the operator is in lowercase.
            if (isset($where['operator'])) {
                $where['operator'] = strtolower($where['operator']);

                // Operator conversions
                $convert = [
                    'regexp'        => 'regex',
                    'elemmatch'     => 'elemMatch',
                    'geointersects' => 'geoIntersects',
                    'geowithin'     => 'geoWithin',
                    'nearsphere'    => 'nearSphere',
                    'maxdistance'   => 'maxDistance',
                    'centersphere'  => 'centerSphere',
                    'uniquedocs'    => 'uniqueDocs',
                ];

                if (array_key_exists($where['operator'], $convert)) {
                    $where['operator'] = $convert[$where['operator']];
                }
            }

            // Convert id's.
            if (isset($where['column']) and ($where['column'] == '_id' or ends_with($where['column'], '._id'))) {
                // Multiple values.
                if (isset($where['values'])) {
                    foreach ($where['values'] as &$value) {
                        $value = $this->convertKey($value);
                    }
                }

                // Single value.
                elseif (isset($where['value'])) {
                    $where['value'] = $this->convertKey($where['value']);
                }
            }

            // Convert DateTime values to UTCDateTime.
            if (isset($where['value']) and $where['value'] instanceof DateTime) {
                $where['value'] = new UTCDateTime($where['value']->getTimestamp() * 1000);
            }

            // The next item in a "chain" of wheres devices the boolean of the
            // first item. So if we see that there are multiple wheres, we will
            // use the operator of the next where.
            if ($i == 0 and count($wheres) > 1 and $where['boolean'] == 'and') {
                $where['boolean'] = $wheres[$i + 1]['boolean'];
            }

            // We use different methods to compile different wheres.
            $method = "compileWhere{$where['type']}";
            $result = $this->{$method}($where);

            // Wrap the where with an $or operator.
            if ($where['boolean'] == 'or') {
                $result = ['$or' => [$result]];
            }

            // If there are multiple wheres, we will wrap it with $and. This is needed
            // to make nested wheres work.
            elseif (count($wheres) > 1) {
                $result = ['$and' => [$result]];
            }

            // Merge the compiled where with the others.
            $compiled = array_merge_recursive($compiled, $result);
        }

        return $compiled;
    }

    protected function compileWhereBasic($where)
    {
        extract($where);

        // Replace like with a Regex instance.
        if ($operator == 'like') {
            $operator = '=';

            // Convert to regular expression.
            $regex = preg_replace('#(^|[^\\\])%#', '$1.*', preg_quote($value));

            // Convert like to regular expression.
            if (! starts_with($value, '%')) {
                $regex = '^' . $regex;
            }
            if (! ends_with($value, '%')) {
                $regex = $regex . '$';
            }

            $value = new Regex($regex, 'i');
        }

        // Manipulate regexp operations.
        elseif (in_array($operator, ['regexp', 'not regexp', 'regex', 'not regex'])) {
            // Automatically convert regular expression strings to Regex objects.
            if (! $value instanceof Regex) {
                $e = explode('/', $value);
                $flag = end($e);
                $regstr = substr($value, 1, -(strlen($flag) + 1));
                $value = new Regex($regstr, $flag);
            }

            // For inverse regexp operations, we can just use the $not operator
            // and pass it a Regex instence.
            if (starts_with($operator, 'not')) {
                $operator = 'not';
            }
        }

        if (! isset($operator) or $operator == '=') {
            $query = [$column => $value];
        } elseif (array_key_exists($operator, $this->conversion)) {
            $query = [$column => [$this->conversion[$operator] => $value]];
        } else {
            $query = [$column => ['$' . $operator => $value]];
        }

        return $query;
    }

    protected function compileWhereNested($where)
    {
        extract($where);

        return $query->compileWheres();
    }

    protected function compileWhereIn($where)
    {
        extract($where);

        return [$column => ['$in' => array_values($values)]];
    }

    protected function compileWhereNotIn($where)
    {
        extract($where);

        return [$column => ['$nin' => array_values($values)]];
    }

    protected function compileWhereNull($where)
    {
        $where['operator'] = '=';
        $where['value'] = null;

        return $this->compileWhereBasic($where);
    }

    protected function compileWhereNotNull($where)
    {
        $where['operator'] = '!=';
        $where['value'] = null;

        return $this->compileWhereBasic($where);
    }

    protected function compileWhereBetween($where)
    {
        extract($where);

        if ($not) {
            return [
                '$or' => [
                    [
                        $column => [
                            '$lte' => $values[0],
                        ],
                    ],
                    [
                        $column => [
                            '$gte' => $values[1],
                        ],
                    ],
                ],
            ];
        } else {
            return [
                $column => [
                    '$gte' => $values[0],
                    '$lte' => $values[1],
                ],
            ];
        }
    }

    protected function compileWhereRaw($where)
    {
        return $where['sql'];
    }

}
