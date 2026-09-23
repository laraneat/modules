<?php

declare(strict_types=1);

namespace Laraneat\Modules\Scaffold;

use Illuminate\Support\Str;
use Laraneat\Modules\Exceptions\InvalidName;

/**
 * @internal
 */
final class Names
{
    /**
     * Composer's own package name format (composer.schema.json).
     */
    private const string PACKAGE = '{^[a-z0-9]([_.-]?[a-z0-9]+)*/[a-z0-9](([_.]|-{1,2})?[a-z0-9]+)*$}D';

    private const string VENDOR = '{^[a-z0-9]([_.-]?[a-z0-9]+)*$}D';

    private const string MODULE = '{^[a-z][a-z0-9]*(-[a-z0-9]+)*$}D';

    private const string PHP_NAMESPACE = '{^[A-Za-z_][A-Za-z0-9_]*(\\\\[A-Za-z_][A-Za-z0-9_]*)*$}D';

    /**
     * Normalize a module name to kebab case: "ShopOrder", "shop_order" and "shop-order" are "shop-order".
     */
    public static function module(string $name): string
    {
        $module = Str::kebab(Str::studly(trim($name)));

        if (preg_match(self::MODULE, $module) !== 1) {
            throw InvalidName::of('module name', $name, 'use latin letters, digits and dashes, starting with a letter.');
        }

        return $module;
    }

    public static function vendor(string $vendor): string
    {
        if (preg_match(self::VENDOR, $vendor) !== 1) {
            throw InvalidName::of('vendor', $vendor, 'use lowercase latin letters, digits and "_", ".", "-" separators.');
        }

        return $vendor;
    }

    public static function package(string $vendor, string $module): string
    {
        return self::vendor($vendor).'/'.self::module($module);
    }

    public static function isPackage(string $package): bool
    {
        return preg_match(self::PACKAGE, $package) === 1;
    }

    public static function assertPackage(string $package): string
    {
        if (! self::isPackage($package)) {
            throw InvalidName::of('package name', $package, 'it does not match the Composer package name format.');
        }

        return $package;
    }

    /**
     * The root namespace of a new module: "Modules" and "shop-order" give "Modules\ShopOrder".
     */
    public static function namespace(string $prefix, string $module): string
    {
        $prefix = trim($prefix, '\\');

        if (preg_match(self::PHP_NAMESPACE, $prefix) !== 1) {
            throw InvalidName::of('namespace', $prefix, 'it is not a valid PHP namespace.');
        }

        return $prefix.'\\'.Str::studly(self::module($module));
    }

    public static function isNamespace(string $namespace): bool
    {
        return preg_match(self::PHP_NAMESPACE, $namespace) === 1;
    }
}
