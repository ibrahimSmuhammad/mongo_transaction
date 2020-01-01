### Laravel Mongo Transaction

##### How to Install:
Assuming you have already installed ```composer require jenssegers/mongodb``` and configured it fully according to the documentation (https://packagist.org/packages/jenssegers/mongodb), now run this to install mongo-transactions:

```composer require hema/mongo_transaction```
### In your Model Or BaseModel Use The Flowing Model :
```php
use Hema\MongoTransaction\Transactions\Eloquent\Model
```

### Instead of Jenssegers Model
```php
use use Jenssegers\Mongodb\Eloquent\Model 
```

### Note : 
* this package created for specific business needs. so, it's may not fit your needs 100%. but you still can fork it and extend or modify it.

* you've to enable replica set in [Mongo](https://docs.mongodb.com/manual/tutorial/deploy-replica-set/#procedure "Replica Set In Mongod").
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
* create();
* insert();
* update();
* delete();
* forceDelete(); 
* restore()
* increment();
* decrement();

* trash(); //custome method for soft-delete
* remove(); //custome method for hard-delete


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
