<?php

namespace Hema\MongoTransaction\Transactions\Eloquent;

use Hema\MongoTransaction\Transactions\Eloquent\Builder;
use Hema\MongoTransaction\Transactions\Transaction;
use Jenssegers\Mongodb\Eloquent\Model as Eloquent;
use Hema\MongoTransaction\Transactions\Query\Builder as QueryBuilder;

abstract class Model extends Eloquent
{

    /**
     * Set a given attribute on the model.
     *
     * @param  string  $key
     * @param  mixed   $value
     */
    public function setAttribute($key, $value)
    {
        // Convert _id to ObjectID.
        if ($key == '_id' and is_string($value)) {
            $builder = $this->newBaseQueryBuilder();

            $value = $builder->convertKey($value);
        }

        // Support keys in dot notation.
        elseif (str_contains($key, '.')) {
            if (in_array($key, $this->getDates()) && $value) {
                $value = $this->fromDateTime($value);
            }

            array_set($this->attributes, $key, $value);

            return;
        }

        parent::setAttribute($key, $value);
    }

    /**
     * Create a new Eloquent query builder for the model.
     *
     * @param  \Hema\MongoTransaction\Transactions\Eloquent\Builder $query
     * @return \Hema\MongoTransaction\Transactions\Eloquent\Builder| \Hema\MongoTransaction\Transactions\Eloquent
     */
    public function newEloquentBuilder($query)
    {
        return new Builder($query);
    }

    /**
     * Get a new query builder instance for the connection.
     *
     * @return Builder
     */
    protected function newBaseQueryBuilder()
    {
        $connection = $this->getConnection();

        return new QueryBuilder($connection, $connection->getPostProcessor());
    }

}
