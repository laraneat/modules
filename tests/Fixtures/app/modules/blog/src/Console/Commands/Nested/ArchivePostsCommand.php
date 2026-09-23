<?php

namespace Modules\Blog\Console\Commands\Nested;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'blog:archive')]
class ArchivePostsCommand extends Command
{
    protected $signature = 'blog:archive';

    public function handle(): int
    {
        $this->info('blog:archive done');

        return self::SUCCESS;
    }
}
