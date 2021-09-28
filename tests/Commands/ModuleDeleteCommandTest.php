<?php

namespace Laraneat\Modules\Tests\Commands;

use Illuminate\Filesystem\Filesystem;
use Laraneat\Modules\Activators\FileActivator;
use Laraneat\Modules\Tests\BaseTestCase;
use Spatie\Snapshots\MatchesSnapshots;

/**
 * @group command
 */
class ModuleDeleteCommandTest extends BaseTestCase
{
    use MatchesSnapshots;

    private Filesystem $finder;
    private FileActivator $activator;

    public function setUp(): void
    {
        parent::setUp();
        $this->finder = $this->app['files'];
        $this->activator = new FileActivator($this->app);
    }

    /** @test */
    public function it_can_delete_a_module_from_disk(): void
    {
        $this->artisan('module:make', ['name' => 'WrongModule']);
        $this->assertDirectoryExists(base_path('app/Modules/WrongModule'));

        $code = $this->artisan('module:delete', ['module' => 'WrongModule']);
        $this->assertDirectoryDoesNotExist(base_path('app/Modules/WrongModule'));
        $this->assertSame(0, $code);
    }

    /** @test */
    public function it_deletes_modules_from_status_file(): void
    {
        $this->artisan('module:make', ['name' => 'WrongModule']);
        $this->assertMatchesSnapshot($this->finder->get($this->activator->getStatusesFilePath()));

        $code = $this->artisan('module:delete', ['module' => 'WrongModule']);
        $this->assertMatchesSnapshot($this->finder->get($this->activator->getStatusesFilePath()));
        $this->assertSame(0, $code);
    }
}
