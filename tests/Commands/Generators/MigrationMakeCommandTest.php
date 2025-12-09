<?php

use Illuminate\Support\Carbon;

use function PHPUnit\Framework\assertFileExists;
use function Spatie\Snapshots\assertMatchesFileSnapshot;

beforeEach(function () {
    $this->setModules([
        realpath(__DIR__ . '/../../fixtures/stubs/modules/valid/article'),
    ], $this->app->basePath('/modules'));

    $date = Carbon::parse('2024-01-01');
    $this->travelTo($date);
    $this->dateStamp = $date->format('Y_m_d_His_');
});

it('generates "plain" migration for the module', function () {
    $this->artisan('module:make:migration', [
        'name' => 'some_plain_migration',
        'module' => 'Article',
        '--stub' => 'plain',
    ])
        ->assertSuccessful();

    $filePath = $this->app->basePath("/modules/article/database/migrations/{$this->dateStamp}some_plain_migration.php");
    assertFileExists($filePath);
    assertMatchesFileSnapshot($filePath);
});

it('generates "plain" migration for the module if the stub is not recognized by name', function () {
    $this->artisan('module:make:migration', [
        'name' => 'some_plain_migration',
        'module' => 'Article',
    ])
        ->assertSuccessful();

    $filePath = $this->app->basePath("/modules/article/database/migrations/{$this->dateStamp}some_plain_migration.php");
    assertFileExists($filePath);
    assertMatchesFileSnapshot($filePath);
});

it('generates "create" migration for the module', function () {
    $this->artisan('module:make:migration', [
        'name' => 'new_articles_table',
        'module' => 'Article',
        '--stub' => 'create',
        '--fields' => 'title:string,excerpt:text,content:text,belongsTo:user:id:users',
    ])
        ->assertSuccessful();

    $filePath = $this->app->basePath("/modules/article/database/migrations/{$this->dateStamp}new_articles_table.php");
    assertFileExists($filePath);
    assertMatchesFileSnapshot($filePath);
});

it('generates "create" migration for the module without fields', function () {
    $this->artisan('module:make:migration', [
        'name' => 'new_articles_table',
        'module' => 'Article',
        '--stub' => 'create',
    ])
        ->assertSuccessful();

    $filePath = $this->app->basePath("/modules/article/database/migrations/{$this->dateStamp}new_articles_table.php");
    assertFileExists($filePath);
    assertMatchesFileSnapshot($filePath);
});

it('automatically detects and generates "create" migration for the module', function () {
    $this->artisan('module:make:migration', [
        'name' => 'create_articles_table',
        'module' => 'Article',
        '--fields' => 'title:string,excerpt:text,content:text,belongsTo:user:id:users',
    ])
        ->assertSuccessful();

    $filePath = $this->app->basePath("/modules/article/database/migrations/{$this->dateStamp}create_articles_table.php");
    assertFileExists($filePath);
    assertMatchesFileSnapshot($filePath);
});

it('generates "add" migration for the module', function () {
    $this->artisan('module:make:migration', [
        'name' => 'modify_articles_table',
        'module' => 'Article',
        '--stub' => 'add',
        '--fields' => 'title:string,excerpt:text,belongsTo:user:id:users',
    ])
        ->assertSuccessful();

    $filePath = $this->app->basePath("/modules/article/database/migrations/{$this->dateStamp}modify_articles_table.php");
    assertFileExists($filePath);
    assertMatchesFileSnapshot($filePath);
});

it('generates "add" migration for the module without fields', function () {
    $this->artisan('module:make:migration', [
        'name' => 'modify_articles_table',
        'module' => 'Article',
        '--stub' => 'add',
    ])
        ->assertSuccessful();

    $filePath = $this->app->basePath("/modules/article/database/migrations/{$this->dateStamp}modify_articles_table.php");
    assertFileExists($filePath);
    assertMatchesFileSnapshot($filePath);
});

it('automatically detects and generates "add" migration for the module', function () {
    $this->artisan('module:make:migration', [
        'name' => 'add_title_to_articles_table',
        'module' => 'Article',
        '--fields' => 'title:string',
    ])
        ->assertSuccessful();

    $filePath = $this->app->basePath("/modules/article/database/migrations/{$this->dateStamp}add_title_to_articles_table.php");
    assertFileExists($filePath);
    assertMatchesFileSnapshot($filePath);
});

it('generates "delete" migration for the module', function () {
    $this->artisan('module:make:migration', [
        'name' => 'modify_articles_table',
        'module' => 'Article',
        '--stub' => 'delete',
        '--fields' => 'title:string,excerpt:text,belongsTo:user:id:users',
    ])
        ->assertSuccessful();

    $filePath = $this->app->basePath("/modules/article/database/migrations/{$this->dateStamp}modify_articles_table.php");
    assertFileExists($filePath);
    assertMatchesFileSnapshot($filePath);
});

it('generates "delete" migration for the module without fields', function () {
    $this->artisan('module:make:migration', [
        'name' => 'modify_articles_table',
        'module' => 'Article',
        '--stub' => 'delete',
    ])
        ->assertSuccessful();

    $filePath = $this->app->basePath("/modules/article/database/migrations/{$this->dateStamp}modify_articles_table.php");
    assertFileExists($filePath);
    assertMatchesFileSnapshot($filePath);
});

it('automatically detects and generates "delete" migration for the module', function () {
    $this->artisan('module:make:migration', [
        'name' => 'delete_title_from_articles_table',
        'module' => 'Article',
        '--fields' => 'title:string',
    ])
        ->assertSuccessful();

    $filePath = $this->app->basePath("/modules/article/database/migrations/{$this->dateStamp}delete_title_from_articles_table.php");
    assertFileExists($filePath);
    assertMatchesFileSnapshot($filePath);
});

it('generates "pivot" migration for the module', function () {
    $this->artisan('module:make:migration', [
        'name' => 'create_article_author_table',
        'module' => 'Article',
        '--stub' => 'pivot',
    ])
        ->expectsQuestion('Enter the name of first table', 'articles')
        ->expectsQuestion('Enter the name of second table', 'authors')
        ->assertSuccessful();

    $filePath = $this->app->basePath("/modules/article/database/migrations/{$this->dateStamp}create_article_author_table.php");
    assertFileExists($filePath);
    assertMatchesFileSnapshot($filePath);
});

describe('table name validation', function () {
    it('rejects invalid table names with special characters', function () {
        // Use a migration name that doesn't auto-detect the table name,
        // so we can enter an invalid one via the prompt
        $this->artisan('module:make:migration', [
            'name' => 'modify_something',
            'module' => 'Article',
            '--stub' => 'create',
        ])
            ->expectsQuestion('Enter the table name', 'invalid-table')
            ->expectsOutputToContain('not valid')
            ->assertFailed();
    });

    it('rejects invalid table names starting with a number', function () {
        $this->artisan('module:make:migration', [
            'name' => 'modify_something',
            'module' => 'Article',
            '--stub' => 'create',
        ])
            ->expectsQuestion('Enter the table name', '123table')
            ->expectsOutputToContain('not valid')
            ->assertFailed();
    });

    it('rejects invalid pivot table names', function () {
        $this->artisan('module:make:migration', [
            'name' => 'create_article_author_table',
            'module' => 'Article',
            '--stub' => 'pivot',
        ])
            ->expectsQuestion('Enter the name of first table', 'invalid-table')
            ->expectsQuestion('Enter the name of second table', 'authors')
            ->expectsOutputToContain('not valid')
            ->assertFailed();
    });

    it('accepts valid table names', function () {
        $this->artisan('module:make:migration', [
            'name' => 'create_valid_table_table',
            'module' => 'Article',
            '--stub' => 'create',
        ])
            ->assertSuccessful();

        $this->artisan('module:make:migration', [
            'name' => 'create__underscore_table_table',
            'module' => 'Article',
            '--stub' => 'create',
            '--force' => true,
        ])
            ->assertSuccessful();
    });
});
