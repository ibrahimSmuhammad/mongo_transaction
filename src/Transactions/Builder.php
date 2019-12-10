<?php

namespace Hema\Transactions;

use Jenssegers\Mongodb\Query\Builder as BaseBuilder;

class Builder extends BaseBuilder
{

  /**
       * Insert a new record into the database.
       *
       * @param  array  $values
       * @return bool
       */
      public function insertWihTransaction(array $values, $session)
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
          $result = $this->collection->insertMany($values, ['session' => $session]);

          return (1 == (int) $result->isAcknowledged());
      }

}
