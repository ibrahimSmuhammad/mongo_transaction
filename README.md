###### mongo_transaction

##### Install
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

#### now you can use transaction this way : 

```php
* Model::startTransaction();

// ........ A bunch of other db operations

* Model::commitTransaction();

// ........ commit when success


* Model::rollbackTransaction();

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

  Post::startTransaction();
  try {
          User::insert($some_data);

          Profile::insert($some_data);

          User::commitTransaction();
          return 'done';

   } catch (\Exception $e) {
          User::rollbackTransaction();
          return $e->getMessage();
   }

```
### And Another Methods Will be Added in next releases.
