<?php

namespace Laraneat\Modules\Tests;

use Laraneat\Modules\Facades\Modules;

class ModulesFacadeTest extends BaseTestCase
{
    /** @test */
    public function it_resolves_the_module_facade()
    {
        $modules = Modules::all();

        $this->assertIsArray($modules);
    }

    /** @test */
    public function it_creates_macros_via_facade()
    {
        $modules = Modules::macro('testMacro', function () {
            return true;
        });

        $this->assertTrue(Modules::hasMacro('testMacro'));
    }

    /** @test */
    public function it_calls_macros_via_facade()
    {
        $modules = Modules::macro('testMacro', function () {
            return 'a value';
        });

        $this->assertEquals('a value', Modules::testMacro());
    }
}
