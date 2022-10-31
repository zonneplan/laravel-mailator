<?php

namespace Binarcode\LaravelMailator\Tests;

use Binarcode\LaravelMailator\LaravelMailatorServiceProvider;
use Illuminate\Contracts\View\Factory;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Mockery as m;
use Orchestra\Testbench\TestCase as Orchestra;
use Swift_Mailer;

class TestCase extends Orchestra
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadMigrationsFrom([
            '--database' => 'mailator',
            '--path' => realpath(__DIR__ . DIRECTORY_SEPARATOR . 'database/migrations'),
        ]);

        $this->loadMigrationsFrom([
            '--database' => 'mailator',
            '--path' => realpath(getcwd() . DIRECTORY_SEPARATOR . 'database/migrations'),
        ]);

        \Illuminate\Database\Eloquent\Factories\Factory::guessFactoryNamesUsing(
            fn(string $modelName) => 'Binarcode\\LaravelMailator\\Tests\\database\\Factories\\' . class_basename($modelName) . 'Factory'
        );
    }

    protected function tearDown(): void
    {
        m::close();
    }

    protected function getPackageProviders($app)
    {
        return [
            LaravelMailatorServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'mailator');

        $databaseDriver = env('DB_DRIVER', 'sqlite');
        $databaseConfig = match ($databaseDriver) {
            'mysql' => [
                'driver' => 'mysql',
                'database' => env('MYSQL_DB_DATABASE', 'mailator'),
                'host' => env('MYSQL_DB_HOST', 'localhost'),
                'port' => env('MYSQL_DB_PORT', '3306'),
                'username' => env('MYSQL_DB_USERNAME', 'root'),
                'password' => env('MYSQL_DB_PASSWORD', 'root'),
                'charset'   => 'utf8',
                'collation' => 'utf8_unicode_ci',
            ],
            'sqlite' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
            ],
            default => throw new \Error("$databaseDriver is not supported"),
        };

        $app['config']->set('database.connections.mailator', $databaseConfig);
    }

    protected function getMocks()
    {
        return ['smtp', m::mock(Factory::class), m::mock(Swift_Mailer::class)];
    }
}
