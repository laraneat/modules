<?php

namespace Laraneat\Modules\Support\Migrations;

class NameParser
{
    /**
     * The migration name.
     */
    protected string $name;

    /**
     * The array data.
     */
    protected array $data = [];

    /**
     * The available schema actions.
     */
    protected array $actions = [
        'create' => [
            'create',
            'make',
        ],
        'delete' => [
            'delete',
            'remove',
        ],
        'add' => [
            'add',
            'update',
            'append',
            'insert',
        ],
    ];

    /**
     * The constructor.
     */
    public function __construct(string $name)
    {
        $this->name = $name;
        $this->data = $this->fetchData();
    }

    /**
     * Get original migration name.
     */
    public function getOriginalName(): string
    {
        return $this->name;
    }

    /**
     * Get schema type or action.
     */
    public function getAction(): string
    {
        return head($this->data);
    }

    /**
     * Get the table will be used.
     */
    public function getTableName(): ?string
    {
        $matches = array_reverse($this->getMatches());

        return array_shift($matches);
    }

    /**
     * Get matches data from regex.
     */
    public function getMatches(): array
    {
        preg_match($this->getPattern(), $this->name, $matches);

        return $matches;
    }

    /**
     * Get name pattern.
     */
    public function getPattern(): string
    {
        return match ($action = $this->getAction()) {
            'add', 'append', 'update', 'insert' => "/{$action}_(.*)_to_(.*)_table/",
            'delete', 'remove', 'alter' => "/{$action}_(.*)_from_(.*)_table/",
            default => "/{$action}_(.*)_table/",
        };
    }

    /**
     * Fetch the migration name to an array data.
     */
    protected function fetchData(): array
    {
        return explode('_', $this->name);
    }

    /**
     * Get the array data.
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Determine whether the given type is same with the current schema action or type.
     */
    public function is(string $type): bool
    {
        return $type === $this->getAction();
    }

    /**
     * Determine whether the current schema action is a adding action.
     */
    public function isAdd(): bool
    {
        return in_array($this->getAction(), $this->actions['add'], true);
    }

    /**
     * Determine whether the current schema action is a deleting action.
     */
    public function isDelete(): bool
    {
        return in_array($this->getAction(), $this->actions['delete'], true);
    }

    /**
     * Determine whether the current schema action is a creating action.
     */
    public function isCreate(): bool
    {
        return in_array($this->getAction(), $this->actions['create'], true);
    }
}
