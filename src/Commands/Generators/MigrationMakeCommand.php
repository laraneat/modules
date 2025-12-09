<?php

namespace Laraneat\Modules\Commands\Generators;

use Illuminate\Contracts\Console\PromptsForMissingInput;
use Illuminate\Support\Str;
use Laraneat\Modules\Enums\ModuleComponentType;
use Laraneat\Modules\Exceptions\InvalidTableName;
use Laraneat\Modules\Support\Generator\Stub;
use Laraneat\Modules\Support\Migrations\NameParser;
use Laraneat\Modules\Support\Migrations\SchemaParser;
use LogicException;

/**
 * @group generator
 */
class MigrationMakeCommand extends BaseComponentGeneratorCommand implements PromptsForMissingInput
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:make:migration
                            {name : The name of the migration}
                            {module? : The name or package name of the app module}
                            {--s|stub= : The stub name to load for this generator}
                            {--f|fields= : The specified fields table}
                            {--t1|tableOne= : The name of first table}
                            {--t2|tableTwo= : The name of second table}
                            {--force : Overwrite the file if it already exists}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate new migration for the specified module.';

    /**
     * The module component type.
     */
    protected ModuleComponentType $componentType = ModuleComponentType::Migration;

    /**
     * Prompt for missing input arguments using the returned questions.
     */
    protected function promptForMissingArgumentsUsing(): array
    {
        return [
            'name' => 'Enter the migration name',
        ];
    }

    protected function getGeneratedFilePath(): string
    {
        return $this->getComponentPath($this->module, $this->getFileName(), $this->componentType);
    }

    protected function getContents(): string
    {
        $stub = $this->getOptionOneOf(
            'stub',
            choices: [
                null,
                'add',
                'create',
                'delete',
                'pivot',
                'plain',
            ],
        );
        $parser = new NameParser($this->argument('name'));

        if ($stub === 'pivot') {
            return $this->generatePivotMigrationContent($parser);
        }

        if ($stub === 'add' || (empty($stub) && $parser->isAdd())) {
            return Stub::create('/migration/add.stub', [
                'table' => $this->getTableName($parser),
                'fieldsUp' => $this->getSchemaParser()->up(),
                'fieldsDown' => $this->getSchemaParser()->down(),
            ])->render();
        }

        if ($stub === 'create' || (empty($stub) && $parser->isCreate())) {
            return Stub::create('/migration/create.stub', [
                'table' => $this->getTableName($parser),
                'fields' => $this->getSchemaParser()->render(),
            ])->render();
        }

        if ($stub === 'delete' || (empty($stub) && $parser->isDelete())) {
            return Stub::create('/migration/delete.stub', [
                'table' => $this->getTableName($parser),
                'fieldsDown' => $this->getSchemaParser()->up(),
                'fieldsUp' => $this->getSchemaParser()->down(),
            ])->render();
        }

        return Stub::create('/migration/plain.stub')->render();
    }

    /**
     * @throws InvalidTableName
     */
    protected function getTableName(NameParser $parser): string
    {
        $tableName = $parser->getTableName();

        if (empty($tableName)) {
            $tableName = $this->ask('Enter the table name');

            if (empty($tableName)) {
                throw new LogicException('The "table" option is required');
            }
        }

        if (! $this->isValidTableName($tableName)) {
            throw InvalidTableName::make($tableName);
        }

        return $tableName;
    }

    /**
     * @throws InvalidTableName
     */
    protected function generatePivotMigrationContent(NameParser $parser): string
    {
        $table = $this->getTableName($parser);

        $tables = explode('_', $table);
        if (count($tables) === 2) {
            $tableOne = Str::plural($tables[0]);
            $tableTwo = Str::plural($tables[1]);
        }

        $tableOne = $this->getOptionOrAsk(
            'tableOne',
            'Enter the name of first table',
            $tableOne ?? ''
        );
        $tableTwo = $this->getOptionOrAsk(
            'tableTwo',
            'Enter the name of second table',
            $tableTwo ?? ''
        );

        if (! $this->isValidTableName($tableOne)) {
            throw InvalidTableName::make($tableOne);
        }

        if (! $this->isValidTableName($tableTwo)) {
            throw InvalidTableName::make($tableTwo);
        }

        return Stub::create('/migration/pivot.stub', [
            'table' => $table,
            'tableOne' => $tableOne,
            'tableTwo' => $tableTwo,
            'columnOne' => $this->convertTableNameToPrimaryColumnName($tableOne),
            'columnTwo' => $this->convertTableNameToPrimaryColumnName($tableTwo),
        ])->render();
    }

    protected function getSchemaParser(): SchemaParser
    {
        return new SchemaParser($this->option('fields'));
    }

    protected function getFileName(): string
    {
        return now()->format('Y_m_d_His_') . Str::snake($this->nameArgument);
    }

    protected function convertTableNameToPrimaryColumnName(string $tableName): string
    {
        return Str::singular($tableName) . '_id';
    }
}
