<?php

namespace Laraneat\Modules\Tests\Commands;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Schema;
use Laraneat\Modules\FileRepository;
use Laraneat\Modules\Tests\BaseTestCase;

/**
 * @group command
 */
abstract class MigrateCommandTest extends BaseTestCase
{
    protected FileRepository $repository;
    protected Filesystem $finder;

    public function setUp(): void
    {
        parent::setUp();
        $this->repository = new FileRepository($this->app);
        $this->finder = $this->app['files'];
    }

    /** @test */
    public function it_migrates_a_module()
    {
        $this->repository->addLocation(__DIR__ . '/../stubs/Article');

        $this->artisan('module:migrate', ['module' => 'Article']);

        dd(Schema::hasTable('articles'), $this->app['db']->table('articles')->get());
    }
}
