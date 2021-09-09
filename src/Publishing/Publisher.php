<?php

namespace Laraneat\Modules\Publishing;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Laraneat\Modules\Contracts\PublisherInterface;
use Laraneat\Modules\Contracts\RepositoryInterface;
use Laraneat\Modules\Module;

abstract class Publisher implements PublisherInterface
{
    /**
     * The module instance.
     *
     * @var Module
     */
    protected Module $module;

    /**
     * The modules repository instance.
     *
     * @var RepositoryInterface|null
     */
    protected ?RepositoryInterface $repository = null;

    /**
     * The laravel console instance.
     *
     * @var Command|null
     */
    protected ?Command $console = null;

    /**
     * The success message will displayed at console.
     *
     * @var string
     */
    protected string $success = '';

    /**
     * The error message will displayed at console.
     *
     * @var string
     */
    protected string $error = '';

    /**
     * Determine whether the result message will shown in the console.
     *
     * @var bool
     */
    protected bool $showMessage = true;

    /**
     * The constructor.
     *
     * @param Module $module
     */
    public function __construct(Module $module)
    {
        $this->module = $module;
    }

    /**
     * Show the result message.
     *
     * @return $this
     */
    public function showMessage()
    {
        $this->showMessage = true;

        return $this;
    }

    /**
     * Hide the result message.
     *
     * @return $this
     */
    public function hideMessage()
    {
        $this->showMessage = false;

        return $this;
    }

    /**
     * Get module instance.
     *
     * @return Module
     */
    public function getModule(): Module
    {
        return $this->module;
    }

    /**
     * Set modules repository instance.
     *
     * @param RepositoryInterface $repository
     * @return $this
     */
    public function setRepository(RepositoryInterface $repository)
    {
        $this->repository = $repository;

        return $this;
    }

    /**
     * Get modules repository instance.
     *
     * @return RepositoryInterface|null
     */
    public function getRepository(): ?RepositoryInterface
    {
        return $this->repository;
    }

    /**
     * Set console instance.
     *
     * @param Command $console
     *
     * @return $this
     */
    public function setConsole(Command $console)
    {
        $this->console = $console;

        return $this;
    }

    /**
     * Get console instance.
     *
     * @return Command|null
     */
    public function getConsole(): ?Command
    {
        return $this->console;
    }

    /**
     * Get laravel filesystem instance.
     *
     * @return Filesystem|null
     */
    public function getFilesystem(): ?Filesystem
    {
        return $this->repository->getFiles();
    }

    /**
     * Get destination path.
     *
     * @return string
     */
    abstract public function getDestinationPath(): string;

    /**
     * Get source path.
     *
     * @return string
     */
    abstract public function getSourcePath(): string;

    /**
     * Publish something.
     */
    public function publish()
    {
        if (!$this->console instanceof Command) {
            $message = "The 'console' property must instance of \\Illuminate\\Console\\Command.";

            throw new \RuntimeException($message);
        }

        if (!$this->getFilesystem()->isDirectory($sourcePath = $this->getSourcePath())) {
            return;
        }

        if (!$this->getFilesystem()->isDirectory($destinationPath = $this->getDestinationPath())) {
            $this->getFilesystem()->makeDirectory($destinationPath, 0775, true);
        }

        if ($this->getFilesystem()->copyDirectory($sourcePath, $destinationPath)) {
            if ($this->showMessage === true) {
                $this->console->line("<info>Published</info>: {$this->module->getStudlyName()}");
            }
        } else {
            $this->console->error($this->error);
        }
    }
}
