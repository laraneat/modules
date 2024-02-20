<?php

namespace Laraneat\Modules\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Laraneat\Modules\Support\Generator\GeneratorHelper;

class StubPublishCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:stub:publish
                    {--existing : Publish and overwrite only the files that have already been published}
                    {--force : Overwrite any existing files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish all laraneat/modules stubs that are available for customization';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $filesystem = new Filesystem();
        $filesystem->ensureDirectoryExists($customStubsPath = GeneratorHelper::getCustomStubsPath());
        $originalStubsPath = realpath(__DIR__ . '/Generators/stubs');

        foreach($filesystem->allFiles($originalStubsPath) as $file) {
            $from = $file->getRealPath();
            $relativePath = Str::after($file->getRealPath(), $originalStubsPath);
            $to = $customStubsPath . '/' . ltrim($relativePath, '/');

            $onlyExisting = $this->option('existing');
            $force = $this->option('force');
            $toIsExists = $filesystem->exists($to);

            if (($onlyExisting && $toIsExists) || (!$onlyExisting && (!$toIsExists || $force))) {
                $filesystem->ensureDirectoryExists(dirname($to));
                $filesystem->put($to, $filesystem->get($from));
            }
        }

        $this->components->info(sprintf('Stubs have been successfully published to the "%s" directory.', $customStubsPath));
    }
}
