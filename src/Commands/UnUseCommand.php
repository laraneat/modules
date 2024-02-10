<?php

namespace Laraneat\Modules\Commands;


class UnUseCommand extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:unuse';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Forget the used module with module:use';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->modules->forgetUsed();

        $this->components->info('Previous module used successfully forgotten.');

        return self::SUCCESS;
    }
}
