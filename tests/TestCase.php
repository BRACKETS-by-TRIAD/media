<?php

namespace Brackets\Media\Test;

use Brackets\Media\MediaServiceProvider;
use Brackets\Media\UrlGenerator\LocalUrlGenerator;
use Exception;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Auth\User;
use Illuminate\Foundation\Exceptions\Handler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\MediaLibrary\MediaLibraryServiceProvider;

abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

    /** @var TestModel */
    protected $testModel;

    /** @var TestModelWithCollections */
    protected $testModelWithCollections;

    public function setUp(): void
    {
        parent::setUp();

        $this->setUpDatabase($this->app);
        $this->setUpTempTestFiles();

        $this->testModel = TestModel::first();
        $this->testModelWithCollections = TestModelWithCollections::first();

        // let's define simple routes
        $this->app['router']->post('/test-model/create', function (Request $request) {
            $sanitized = $request->only([
                'name',
            ]);

            $testModel = TestModelWithCollections::create($sanitized);

            return $testModel;
        });

        $this->app['router']->post('/test-model-disabled/create', function (Request $request) {
            $sanitized = $request->only([
                'name',
            ]);

            $testModel = TestModelWithCollectionsDisabledAutoProcess::create($sanitized);

            return $testModel;
        });
    }

    /**
     * @param Application $app
     *
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            MediaLibraryServiceProvider::class,
            MediaServiceProvider::class,
//            AdminAuthServiceProvider::class
        ];
    }

    /**
     * @param Application $app
     */
    protected function getEnvironmentSetUp($app)
    {
        $this->initializeDirectory($this->getTempDirectory());

        if (env('DB_CONNECTION') === 'pgsql') {
            $app['config']->set('database.default', 'pgsql');
            $app['config']->set('database.connections.pgsql', [
                'driver' => 'pgsql',
                'host' => 'testing',
                'port' => '5432',
                'database' => 'homestead',
                'username' => 'homestead',
                'password' => 'secret',
                'charset' => 'utf8',
                'prefix' => '',
                'schema' => 'public',
                'sslmode' => 'prefer',
            ]);
        } else {
            $app['config']->set('database.default', 'sqlite');
            $app['config']->set('database.connections.sqlite', [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
            ]);
        }

        // FIXME these config setting needs to have a look
        $app['config']->set('filesystems.disks.media', [
            'driver' => 'local',
            'root' => $this->getMediaDirectory(),
        ]);


        // FIXME these config setting needs to have a look
        $app['config']->set('filesystems.disks.media_private', [

            'driver' => 'local',
             'root' => $this->getMediaDirectory('storage'),
        ]);

        $app['config']->set('filesystems.disks.uploads', [
            'driver' => 'local',
            'root' => $this->getUploadsDirectory(),
        ]);

        $app['config']->set('media-collections', [
            'public_disk' => 'media',
            'private_disk' => 'media_private',

            'auto_process' => true,
        ]);

        $app['config']->set('medialibrary.url_generator', LocalUrlGenerator::class);

        // FIXME these config setting needs to have a look
        $app->bind('path.public', function () {
            return $this->getTempDirectory();
        });

        // FIXME these config setting needs to have a look
        $app->bind('path.storage', function () {
            return $this->getTempDirectory();
        });

        $app['config']->set('app.key', '6rE9Nz59bGRbeMATftriyQjrpF7DcOQm');
    }

    /**
     * @param Application $app
     */
    protected function setUpDatabase($app)
    {
        $app['db']->connection()->getSchemaBuilder()->create('test_models', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->integer('width')->nullable();
        });

        TestModel::create(['name' => 'test']);

        include_once 'vendor/spatie/laravel-medialibrary/database/migrations/create_media_table.php.stub';

        (new \CreateMediaTable())->up();
    }

    // FIXME what is this method for?
    protected function setUpTempTestFiles()
    {
        $this->initializeDirectory($this->getTestFilesDirectory());
        $this->initializeDirectory($this->getUploadsDirectory());
        File::copyDirectory(__DIR__.'/testfiles', $this->getTestFilesDirectory());
        File::copyDirectory(__DIR__.'/testfiles', $this->getUploadsDirectory());
    }

    protected function initializeDirectory($directory)
    {
        if (File::isDirectory($directory)) {
            File::deleteDirectory($directory);
        }
        File::makeDirectory($directory);
    }

    public function getTempDirectory($suffix = '')
    {
        return __DIR__.'/temp'.($suffix == '' ? '' : '/'.$suffix);
    }

    public function getMediaDirectory($suffix = '')
    {
        return $this->getTempDirectory('media').($suffix == '' ? '' : '/'.$suffix);
    }

    public function getUploadsDirectory($suffix = '')
    {
        return $this->getTempDirectory('uploads').($suffix == '' ? '' : '/'.$suffix);
    }

    public function getTestFilesDirectory($suffix = '')
    {
        return $this->getTempDirectory('app').($suffix == '' ? '' : '/'.$suffix);
    }

    public function disableAuthorization()
    {
        $this->actingAs(new User, 'admin');
        Gate::define('admin', function ($user) {
            return true;
        });
        Gate::define('admin.upload', function ($user) {
            return true;
        });
    }

    protected function disableExceptionHandling()
    {
        $this->app->instance(ExceptionHandler::class, new class extends Handler {
            public function __construct()
            {
            }

            public function report(Exception $e)
            {
                // no-op
            }

            public function render($request, Exception $e)
            {
                throw $e;
            }
        });
    }
}
