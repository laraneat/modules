<?php

declare(strict_types=1);

use Laraneat\Modules\Tests\Generators\GeneratorsTestCase;
use Laraneat\Modules\Tests\TestCase;
use Laraneat\Modules\Tests\UnitTestCase;

pest()->extend(TestCase::class)->in('Feature');
pest()->extend(UnitTestCase::class)->in('Unit');
pest()->extend(PHPUnit\Framework\TestCase::class)->in('Integration');
pest()->extend(GeneratorsTestCase::class)->in('Generators');
