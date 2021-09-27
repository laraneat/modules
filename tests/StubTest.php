<?php

namespace Laraneat\Modules\Tests;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Laraneat\Modules\Support\Stub;

class StubTest extends BaseTestCase
{
    private Filesystem $finder;

    public function setUp(): void
    {
        parent::setUp();
        $this->finder = $this->app['files'];
    }

    public function tearDown(): void
    {
        parent::tearDown();
        $this->finder->delete([
            base_path('my-command.php'),
            base_path('stub-override-exists.php'),
            base_path('stub-override-not-exists.php'),
        ]);
    }

    /** @test */
    public function it_initialises_a_stub_instance()
    {
        $stub = new Stub('/model.stub', [
            'name' => 'Name',
            'factory' => 'Some\\ModelFactory'
        ]);

        $this->assertTrue(Str::contains($stub->getPath(), 'Commands/Generators/stubs/model.stub'));
        $this->assertEquals([
            'name' => 'Name',
            'factory' => 'Some\\ModelFactory'
        ], $stub->getReplaces());
    }

    /** @test */
    public function it_sets_new_replaces_array()
    {
        $stub = new Stub('/model.stub', [
            'name' => 'Name',
        ]);

        $stub->replace([
            'factory' => 'Some\\New\\ModelFactory'
        ]);
        $this->assertEquals([
            'factory' => 'Some\\New\\ModelFactory'
        ], $stub->getReplaces());
    }

    /** @test */
    public function it_stores_stub_to_specific_path()
    {
        $stub = new Stub('/command.stub', [
            'command' => 'my:command',
            'namespace' => 'Article\\Commands',
            'class' => 'MyCommand',
        ]);

        $stub->saveTo(base_path(), 'my-command.php');

        $this->assertTrue($this->finder->exists(base_path('my-command.php')));
    }

    /** @test */
    public function it_sets_new_path()
    {
        $stub = new Stub('/model.stub', [
            'name' => 'Name',
        ]);

        $stub->setPath('/new-path/');

        $this->assertTrue(Str::contains($stub->getPath(), 'Commands/Generators/stubs/new-path/'));
    }
}
