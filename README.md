###### mongo_transaction

##### Install
Assuming you have already installed ```composer require jenssegers/mongodb``` and configured it fully according to the documentation (https://packagist.org/packages/jenssegers/mongodb), now run this to install mongo-transactions:

```composer require hema/mongo_transaction```

### now you can use transaction this way : 

```php
* Model::startTransaction();

// ........ A bunch of other db operations

* Model::commitTransaction();

// ........ commit when success


* Model::rollbackTransaction();

// ........ Or rollback when error occure


```
Now You Can Use These Methods with transaction

```php
* insert();
* update();
* delete();
* increment();
* decrement();
