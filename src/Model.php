<?php

namespace Hema\MongoTransaction;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;
use Hema\MongoTransaction\Builder as QueryBuilder;
class Model extends Eloquent
{
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
