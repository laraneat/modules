<?php

namespace Modules\Blog\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'blog:publish')]
class PublishPostsCommand extends Command
{
    protected $signature = 'blog:publish';

    public function handle(): int
    {
        $this->info('blog:publish done');

        return self::SUCCESS;
    }
}
