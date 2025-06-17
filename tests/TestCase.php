<?php

declare(strict_types=1);

namespace Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use Scottlaurent\Accounting\Providers\AccountingServiceProvider;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            AccountingServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
            'foreign_key_constraints' => true,
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();
        
        // Ensure the migrations table exists
        $this->createMigrationsTable();
        
        // Load and run migrations
        $migrationPaths = [
            __DIR__ . '/../src/migrations',
        ];
        
        foreach ($migrationPaths as $path) {
            $this->loadMigrationsFrom($path);
        }
        
        // Run migrations for the test database
        $this->artisan('migrate:fresh', [
            '--database' => 'testbench',
            '--path' => 'src/migrations',
            '--realpath' => true,
        ]);
    }
    
    protected function createMigrationsTable(): void
    {
        if (!\Schema::hasTable('migrations')) {
            $migration = new class extends \Illuminate\Database\Migrations\Migration {
                public function up(): void
                {
                    $schema = app('db')->connection()->getSchemaBuilder();
                    $schema->create('migrations', function (\Illuminate\Database\Schema\Blueprint $table) {
                        $table->increments('id');
                        $table->string('migration');
                        $table->integer('batch');
                    });
                }
                
                public function down(): void
                {
                    $schema = app('db')->connection()->getSchemaBuilder();
                    $schema->dropIfExists('migrations');
                }
            };
            
            $migration->up();
        }
    }
}
