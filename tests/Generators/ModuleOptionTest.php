<?php

declare(strict_types=1);

use Illuminate\Console\GeneratorCommand;
use Illuminate\Console\MigrationGeneratorCommand;
use Illuminate\Database\Console\Migrations\MigrateMakeCommand;
use Illuminate\Support\Facades\Artisan;

it('adds --module to every generator', function () {
    $generators = collect(Artisan::all())->filter(static fn (object $command): bool => $command instanceof GeneratorCommand
        || $command instanceof MigrateMakeCommand
        || $command instanceof MigrationGeneratorCommand);

    expect($generators->count())->toBeGreaterThan(40)
        ->and($generators->reject(static fn (object $command): bool => $command->getDefinition()->hasOption('module'))->keys()->all())->toBe([])
        ->and($generators->keys()->all())->toContain('make:action', 'make:data', 'make:migration', 'make:queue-table');
});

it('generates into the module', function (string $command, array $expected) {
    $files = $this->generate($command.' --module=blog');

    expect(array_keys($files))->toEqualCanonicalizing(array_keys($expected));

    foreach ($expected as $path => $contains) {
        if (str_ends_with($path, '.php') && ! str_ends_with($path, '.blade.php')) {
            PhpToken::tokenize($files[$path], TOKEN_PARSE);
        }

        expect($files[$path])->toContain(...$contains);
    }
})->with([
    'action' => ['make:action PublishPost', ['modules/blog/src/Actions/PublishPost.php' => ['namespace Modules\\Blog\\Actions;', 'class PublishPost']]],
    'cast' => ['make:cast Money', ['modules/blog/src/Casts/Money.php' => ['namespace Modules\\Blog\\Casts;']]],
    'channel' => ['make:channel PostChannel', ['modules/blog/src/Broadcasting/PostChannel.php' => ['namespace Modules\\Blog\\Broadcasting;']]],
    'class' => ['make:class Support/Slugger', ['modules/blog/src/Support/Slugger.php' => ['namespace Modules\\Blog\\Support;']]],
    'command' => ['make:command SendDigest', ['modules/blog/src/Console/Commands/SendDigest.php' => ['namespace Modules\\Blog\\Console\\Commands;']]],
    'component' => ['make:component Forms/Input', [
        'modules/blog/src/View/Components/Forms/Input.php' => ['namespace Modules\\Blog\\View\\Components\\Forms;', "view('blog::components.forms.input')"],
        'modules/blog/resources/views/components/forms/input.blade.php' => ['<div>'],
    ]],
    'config' => ['make:config blog-extra', ['modules/blog/config/blog-extra.php' => ['return [']]],
    'controller' => ['make:controller Api/PostController --api --model=Post', ['modules/blog/src/Http/Controllers/Api/PostController.php' => [
        'namespace Modules\\Blog\\Http\\Controllers\\Api;',
        'use Modules\\Blog\\Models\\Post;',
    ]]],
    'data' => ['make:data PostData', ['modules/blog/src/Data/PostData.php' => ['namespace Modules\\Blog\\Data;']]],
    'enum' => ['make:enum Status', ['modules/blog/src/Status.php' => ['namespace Modules\\Blog;', 'enum Status']]],
    'event' => ['make:event PostPublished', ['modules/blog/src/Events/PostPublished.php' => ['namespace Modules\\Blog\\Events;']]],
    'exception' => ['make:exception PostNotFound', ['modules/blog/src/Exceptions/PostNotFound.php' => ['namespace Modules\\Blog\\Exceptions;']]],
    'factory' => ['make:factory CommentFactory --model=Comment', ['modules/blog/database/factories/CommentFactory.php' => [
        'namespace Modules\\Blog\\Database\\Factories;',
        'use Modules\\Blog\\Models\\Comment;',
    ]]],
    'interface' => ['make:interface Contracts/Publisher', ['modules/blog/src/Contracts/Publisher.php' => ['namespace Modules\\Blog\\Contracts;']]],
    'job' => ['make:job PublishPost', ['modules/blog/src/Jobs/PublishPost.php' => ['namespace Modules\\Blog\\Jobs;']]],
    'job middleware' => ['make:job-middleware RateLimited', ['modules/blog/src/Jobs/Middleware/RateLimited.php' => ['namespace Modules\\Blog\\Jobs\\Middleware;']]],
    'listener' => ['make:listener SendNotification --event=PostPublished', ['modules/blog/src/Listeners/SendNotification.php' => [
        'namespace Modules\\Blog\\Listeners;',
        'use Modules\\Blog\\Events\\PostPublished;',
    ]]],
    'mail' => ['make:mail PostPublished --markdown', [
        'modules/blog/src/Mail/PostPublished.php' => ['namespace Modules\\Blog\\Mail;', "markdown: 'blog::mail.post-published'"],
        'modules/blog/resources/views/mail/post-published.blade.php' => ['<x-mail::message>'],
    ]],
    'mail with a view' => ['make:mail PostPublished --view=emails.post', [
        'modules/blog/src/Mail/PostPublished.php' => ["view: 'blog::emails.post'"],
        'modules/blog/resources/views/emails/post.blade.php' => ['<div>'],
    ]],
    'middleware' => ['make:middleware EnsureAuthor', ['modules/blog/src/Http/Middleware/EnsureAuthor.php' => ['namespace Modules\\Blog\\Http\\Middleware;']]],
    'migration' => ['make:migration create_comments_table', ['modules/blog/database/migrations/{date}_create_comments_table.php' => ["Schema::create('comments'"]]],
    'model' => ['make:model Tag', ['modules/blog/src/Models/Tag.php' => ['namespace Modules\\Blog\\Models;', 'class Tag extends Model']]],
    'notification' => ['make:notification PostPublished --markdown=notifications.post', [
        'modules/blog/src/Notifications/PostPublished.php' => ['namespace Modules\\Blog\\Notifications;', "->markdown('blog::notifications.post')"],
        'modules/blog/resources/views/notifications/post.blade.php' => ['<x-mail::message>'],
    ]],
    'observer' => ['make:observer PostObserver --model=Post', ['modules/blog/src/Observers/PostObserver.php' => [
        'namespace Modules\\Blog\\Observers;',
        'use Modules\\Blog\\Models\\Post;',
    ]]],
    'policy' => ['make:policy PostPolicy --model=Post', ['modules/blog/src/Policies/PostPolicy.php' => [
        'namespace Modules\\Blog\\Policies;',
        'use Modules\\Blog\\Models\\Post;',
    ]]],
    'request' => ['make:request StorePostRequest', ['modules/blog/src/Http/Requests/StorePostRequest.php' => ['namespace Modules\\Blog\\Http\\Requests;']]],
    'resource' => ['make:resource PostCollection --collection', ['modules/blog/src/Http/Resources/PostCollection.php' => ['namespace Modules\\Blog\\Http\\Resources;']]],
    'rule' => ['make:rule Slug', ['modules/blog/src/Rules/Slug.php' => ['namespace Modules\\Blog\\Rules;']]],
    'scope' => ['make:scope PublishedScope', ['modules/blog/src/Models/Scopes/PublishedScope.php' => ['namespace Modules\\Blog\\Models\\Scopes;']]],
    'seeder' => ['make:seeder Deployment/TagSeeder_3', ['modules/blog/database/seeders/Deployment/TagSeeder_3.php' => [
        'namespace Modules\\Blog\\Database\\Seeders\\Deployment;',
        'class TagSeeder_3 extends Seeder',
    ]]],
    'feature test' => ['make:test Api/PostTest --phpunit', ['modules/blog/tests/Feature/Api/PostTest.php' => [
        'namespace Modules\\Blog\\Tests\\Feature\\Api;',
        'use Tests\\TestCase;',
    ]]],
    'unit test' => ['make:test PostTest --unit --phpunit', ['modules/blog/tests/Unit/PostTest.php' => ['namespace Modules\\Blog\\Tests\\Unit;']]],
    'pest test' => ['make:test PostTest --pest', ['modules/blog/tests/Feature/PostTest.php' => ["test('example'"]]],
    'trait' => ['make:trait Concerns/HasSlug', ['modules/blog/src/Concerns/HasSlug.php' => ['namespace Modules\\Blog\\Concerns;', 'trait HasSlug']]],
    'view' => ['make:view posts.show --test --phpunit', [
        'modules/blog/resources/views/posts/show.blade.php' => ['<div>'],
        'modules/blog/tests/Feature/View/Posts/ShowTest.php' => ['namespace Modules\\Blog\\Tests\\Feature\\View\\Posts;', "\$this->view('blog::posts.show'"],
    ]],
    'queue table' => ['make:queue-table', ['modules/blog/database/migrations/{date}_create_jobs_table.php' => ["Schema::create('jobs'"]]],
    'session table' => ['make:session-table', ['modules/blog/database/migrations/{date}_create_sessions_table.php' => ["Schema::create('sessions'"]]],
]);
