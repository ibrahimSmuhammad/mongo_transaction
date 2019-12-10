<?php

namespace Hema\MongoTransaction;

use Illuminate\Support\ServiceProvider;

class MongoTransactionServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
         $this->app->make('Hema\Transactions\Builder');
         $this->app->make('Hema\Transactions\Model');
    }
}
