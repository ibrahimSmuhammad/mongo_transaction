### Laravel Mongo Transaction

##### How to Install:
Assuming you have already installed ```composer require jenssegers/mongodb``` and configured it fully according to the documentation (https://packagist.org/packages/jenssegers/mongodb), now run this to install mongo-transactions:

```composer require hema/mongo_transaction```
### Extend The Flowing Model :
```php
use Hema\MongoTransaction\Transactions\Model
```

### Instead of Jenssegers Model
```php
use use Jenssegers\Mongodb\Eloquent\Model 
```

### Note : 
you've to enable replica set in [Mongo](https://docs.mongodb.com/manual/tutorial/deploy-replica-set/#procedure "Replica Set In Mongod").
```php
~$ sudo mongod --replSet "rs0" 
```

and then run mongo
```php
~$ sudo mongo 
```

#### now you can use transaction this way : 

```php
use Hema\MongoTransaction\Transactions\Transaction

```


```php
* Transaction::start();

// ........ A bunch of other db operations

* Transaction::commit();

// ........ commit when success


* Transaction::rollback();

// ........ Or rollback when error occure


```
### Now You Can Use These Methods with transaction

```php
* insert();
* update();
* delete();
* increment();
* decrement();


```

#### Example : 

```php

    Transaction::start();
  try {
          User::insert($some_data);

          Profile::insert($some_data);

        Transaction::commit();
        return 'done';

   } catch (\Exception $e) {
        Transaction::rollback();
          return $e->getMessage();
   }

```

### And Another Methods Will be Added in next releases.
