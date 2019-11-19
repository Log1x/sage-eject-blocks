<?php

namespace Log1x\EjectBlocks;

use Roots\Acorn\ServiceProvider;
use Log1x\EjectBlocks\Console\Commands\EjectBlocksCommand;

class EjectBlocksServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->commands([
            EjectBlocksCommand::class,
        ]);
    }
}
