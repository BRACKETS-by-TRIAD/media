<?php namespace Brackets\Media;

use Illuminate\Support\ServiceProvider;

class MediaServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../install-stubs/config' => base_path('config')
            ], 'config');
        }
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
            __DIR__ . '/../config/filesystems.php', 'filesystems.disks'
        );
    }
}
