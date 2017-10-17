<?php namespace Brackets\Media;

use Illuminate\Support\ServiceProvider;

class MediaServiceProvider extends ServiceProvider {
	/**
	 * Bootstrap any application services.
	 *
	 * @return void
	 */
	public function boot() {
		$this->loadRoutesFrom( __DIR__ . '/../routes/web.php' );

		if ( $this->app->runningInConsole() ) {
			$this->publishes( [
				__DIR__ . '/../install-stubs/config/media-collections.php' => config_path( 'media-collections.php' )
			], 'config' );
		}
	}

	/**
	 * Register any application services.
	 *
	 * @return void
	 */
	public function register() {
		//FIXME it would be nice if you could somehow publish into filesystems
		$this->mergeConfigFrom(
			__DIR__ . '/../config/filesystems.php', 'filesystems.disks'
		);

		$this->mergeConfigFrom(
			__DIR__ . '/../install-stubs/config/media-collections.php', 'media-collections'
		);
	}
}
