<?php namespace Brackets\Media;

use Illuminate\Support\ServiceProvider;

class MediaProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //FIXME:: ako sa routes prefixuju s brackets/admin?
        $this->loadRoutesFrom(__DIR__.'/Http/routes.php'); 
        
        $this->publishes([
            __DIR__.'/config' => base_path('config')
        ], 'config');
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //FIXME: lepsie by bolo keby sa to dalo publishnut do filesystems
        $this->mergeConfigFrom(
            __DIR__.'/config/filesystems.php', 'filesystems.disks'
        );
    }
}
