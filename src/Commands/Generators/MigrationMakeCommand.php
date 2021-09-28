<?php

namespace Laraneat\Modules\Commands\Generators;

use Illuminate\Support\Str;
use Laraneat\Modules\Module;
use Laraneat\Modules\Support\Generator\GeneratorHelper;
use Laraneat\Modules\Support\Migrations\NameParser;
use Laraneat\Modules\Support\Migrations\SchemaParser;
use Laraneat\Modules\Support\Stub;
use Laraneat\Modules\Traits\ModuleCommandTrait;
use LogicException;
use Symfony\Component\Console\Input\InputOption;

/**
 * @group generator
 */
class MigrationMakeCommand extends ComponentGeneratorCommand
{
    use ModuleCommandTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'module:make:migration';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate new migration for the specified module.';

    /**
     * The stub name to load for this generator.
     *
     * @var string|null
     */
    protected ?string $stub = null;

    /**
     * Module instance.
     *
     * @var Module
     */
    protected Module $module;

    /**
     * Component type.
     *
     * @var string
     */
    protected string $componentType;

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions(): array
    {
        return [
            ['stub', 's', InputOption::VALUE_REQUIRED, 'The stub name to load for this generator.', ''],
            ['fields', 'f', InputOption::VALUE_REQUIRED, 'The specified fields table.', null],
            ['tableOne', 't1', InputOption::VALUE_REQUIRED, 'The name of first table.'],
            ['tableTwo', 't2', InputOption::VALUE_REQUIRED, 'The name of second table.'],
        ];
    }

    protected function getSchemaParser(): SchemaParser
    {
        return new SchemaParser($this->option('fields'));
    }

    protected function getFileName(): string
    {
        return date('Y_m_d_His_') . Str::snake($this->getTrimmedArgument('name'));
    }

    protected function prepare()
    {
        $this->module = $this->getModule();
        $this->stub = $this->getOptionOneOf(
            'stub',
            ['', 'plain', 'add', 'create', 'delete', 'pivot'],
        );
        $this->componentType = 'migration';
    }

    protected function getDestinationFilePath(): string
    {
        $componentPath = GeneratorHelper::component($this->componentType)->getFullPath($this->module);

        return $componentPath . '/' . $this->getFileName() . '.php';
    }

    protected function getTemplateContents(): string
    {
        $parser = new NameParser($this->argument('name'));

        if ($this->stub === 'pivot') {
            return $this->generatePivotMigrationContent($parser);
        }

        if ($this->stub === 'add' || (empty($this->stub) && $parser->isAdd())) {
            return Stub::create('/migration/add.stub', [
                'table' => $this->getTableName($parser),
                'fieldsUp' => $this->getSchemaParser()->up(),
                'fieldsDown' => $this->getSchemaParser()->down(),
            ])->render();
        }

        if ($this->stub === 'create' || (empty($this->stub) && $parser->isCreate())) {
            return Stub::create('/migration/create.stub', [
                'table' => $this->getTableName($parser),
                'fields' => $this->getSchemaParser()->render(),
            ])->render();
        }

        if ($this->stub === 'delete' || (empty($this->stub) && $parser->isDelete())) {
            return Stub::create('/migration/delete.stub', [
                'table' => $this->getTableName($parser),
                'fieldsDown' => $this->getSchemaParser()->up(),
                'fieldsUp' => $this->getSchemaParser()->down(),
            ])->render();
        }

        return Stub::create('/migration/plain.stub')->render();
    }

    protected function getTableName(NameParser $parser): string
    {
        $tableName = $parser->getTableName();

        if (empty($tableName)) {
            $tableName = $this->ask('Enter the table name');

            if (empty($tableName)) {
                throw new LogicException('The "table" option is required');
            }
        }

        return $tableName;
    }

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
            'Enter the name of first table.',
            $tableOne ?? '',
            true
        );
        $tableTwo = $this->getOptionOrAsk(
            'tableTwo',
            'Enter the name of second table.',
            $tableTwo ?? '',
            true
        );

        return Stub::create('/migration/pivot.stub', [
            'table' => $table,
            'tableOne' => $tableOne,
            'tableTwo' => $tableTwo,
            'columnOne' => $this->convertTableNameToPrimaryColumnName($tableOne),
            'columnTwo' => $this->convertTableNameToPrimaryColumnName($tableTwo)
        ])->render();
    }

    protected function convertTableNameToPrimaryColumnName(string $tableName): string
    {
        return Str::singular($tableName) . '_id';
    }
}
