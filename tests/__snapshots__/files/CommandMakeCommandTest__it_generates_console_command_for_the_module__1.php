<?php

namespace Modules\Author\UI\CLI\Commands;

use Illuminate\Console\Command;

class SomeAuthorCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'author:some-command {--foo : foo option}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Some author command description';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        return self::SUCCESS;
    }
}
