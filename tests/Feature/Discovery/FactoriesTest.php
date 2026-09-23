<?php

declare(strict_types=1);

use Database\Factories\WidgetFactory;
use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Blog\Database\Factories\PostFactory;
use Modules\Blog\Models\Post;
use Modules\ShopOrder\Database\Factories\Entities\OrderFactory;
use Modules\ShopOrder\Database\Factories\InvoiceFactory;

it('pairs module models with the factories of the module', function (string $model, string $factory) {
    expect(Factory::resolveFactoryName($model))->toBe($factory);
})->with([
    'model' => ['Modules\\Blog\\Models\\Post', 'Modules\\Blog\\Database\\Factories\\PostFactory'],
    'nested model' => ['Modules\\Blog\\Models\\Admin\\Role', 'Modules\\Blog\\Database\\Factories\\Admin\\RoleFactory'],
    'model outside Models' => ['Modules\\ShopOrder\\Entities\\Order', 'Modules\\ShopOrder\\Database\\Factories\\Entities\\OrderFactory'],
    'application model' => ['App\\Models\\User', 'Database\\Factories\\UserFactory'],
    'application class' => ['App\\User', 'Database\\Factories\\UserFactory'],
    'package model' => ['Vendor\\Package\\Models\\Thing', 'Database\\Factories\\Vendor\\Package\\Models\\ThingFactory'],
    'unknown module namespace' => ['Modules\\Wiki\\Models\\Page', 'Database\\Factories\\Modules\\Wiki\\Models\\PageFactory'],
]);

it('pairs module factories with the models of the module', function () {
    expect(PostFactory::new()->modelName())->toBe(Post::class)
        ->and(Post::factory()->make()->title)->toBe('Hello');
});

it('falls back to the root namespace of the module when the model is not in Models', function () {
    $this->files(['modules/shop-order/database/factories/InvoiceFactory.php' => <<<'PHP'
        <?php

        namespace Modules\ShopOrder\Database\Factories;

        use Illuminate\Database\Eloquent\Factories\Factory;

        class InvoiceFactory extends Factory
        {
            public function definition(): array
            {
                return [];
            }
        }
        PHP]);

    expect((new InvoiceFactory)->modelName())->toBe('Modules\\ShopOrder\\Invoice');
});

it('pairs a factory with a module model outside Models', function () {
    $this->files([
        'modules/shop-order/src/Entities/Order.php' => '<?php namespace Modules\ShopOrder\Entities; class Order extends \Illuminate\Database\Eloquent\Model {}',
        'modules/shop-order/database/factories/Entities/OrderFactory.php' => '<?php namespace Modules\ShopOrder\Database\Factories\Entities; class OrderFactory extends \Illuminate\Database\Eloquent\Factories\Factory { public function definition(): array { return []; } }',
    ]);

    expect((new OrderFactory)->modelName())->toBe('Modules\\ShopOrder\\Entities\\Order');
});

it('keeps the default behavior for application factories', function () {
    eval('namespace Database\\Factories; class WidgetFactory extends \\Illuminate\\Database\\Eloquent\\Factories\\Factory { public function definition(): array { return []; } }');

    expect((new WidgetFactory)->modelName())->toBe('App\\Widget');
});

it('picks the most specific module namespace', function () {
    $this->files(['modules/blog-comments/composer.json' => json_encode([
        'name' => 'app/blog-comments',
        'autoload' => ['psr-4' => ['Modules\\Blog\\Comments\\' => 'src/']],
    ])]);
    $this->reboot();

    expect(Factory::resolveFactoryName('Modules\\Blog\\Comments\\Models\\Comment'))
        ->toBe('Modules\\Blog\\Comments\\Database\\Factories\\CommentFactory')
        ->and(Factory::resolveFactoryName('Modules\\Blog\\Models\\Post'))
        ->toBe('Modules\\Blog\\Database\\Factories\\PostFactory');
});

it('keeps the default resolvers without modules', function () {
    $this->files(['config/modules.php' => '<?php return ["path" => base_path("empty")];']);
    Factory::flushState();
    $this->reboot();

    expect(Factory::resolveFactoryName('Modules\\Blog\\Models\\Post'))
        ->toBe('Database\\Factories\\Modules\\Blog\\Models\\PostFactory');
});

it('falls back to the App namespace without an application', function () {
    $container = Container::getInstance();
    Container::setInstance(new Container);

    try {
        expect(Factory::resolveFactoryName('App\\Models\\User'))->toBe('Database\\Factories\\UserFactory');
    } finally {
        Container::setInstance($container);
    }
});
