<?php

declare(strict_types=1);

namespace Laraneat\Modules\Facades;

use Illuminate\Support\Facades\Facade;
use Laraneat\Modules\ModuleRepository;

/**
 * @method static array<string, \Laraneat\Modules\Module> all()
 * @method static \Laraneat\Modules\Module|null find(string $name)
 * @method static \Laraneat\Modules\Module get(string $name)
 * @method static list<class-string<\Illuminate\Database\Seeder>> seeders(string ...$directories)
 *
 * @see ModuleRepository
 */
final class Modules extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ModuleRepository::class;
    }
}
