# Laraneat Modules

A Laravel package that provides a powerful modular architecture system for organizing large-scale applications into self-contained, reusable modules.

## Table of Contents

- [Overview](#overview)
- [Performance Comparison](#performance-comparison-with-nwidartlaravel-modules)
- [Architecture Diagram](#architecture-diagram)
- [Installation](#installation)
- [Quick Start](#quick-start)
- [Module Structure](#module-structure)
- [Core Concepts](#core-concepts)
- [Configuration](#configuration)
- [Artisan Commands](#artisan-commands)
- [Component Types](#component-types)
- [Module Presets](#module-presets)
- [Best Practices](#best-practices)

## Overview

Laraneat Modules helps you build maintainable, scalable Laravel applications. Inspired by the [Porto SAP (Software Architectural Pattern)](https://github.com/Mahmoudz/Porto), it encourages organizing code by business domains (modules) instead of technical layers.

### Why Modular Architecture?

| Traditional Laravel | Modular Approach |
|---------------------|------------------|
| All controllers in `app/Http/Controllers` | Each module has its own controllers |
| All models in `app/Models` | Each module has its own models |
| Coupled, hard to maintain | Decoupled, easy to maintain |
| Difficult to reuse | Easy to extract and reuse |
| Complex routing | Module-scoped routing |

## Performance Comparison with nWidart/laravel-modules

This package is designed with performance in mind. **With caching enabled, it adds virtually zero overhead** — the cached manifest is a simple PHP array that loads in microseconds, and all core services use lazy loading.

Here's how it compares to the popular [nWidart/laravel-modules](https://github.com/nWidart/laravel-modules):

### Key Differences

| Feature | Laraneat Modules | nWidart/laravel-modules |
|---------|------------------|-------------------------|
| **Module manifest** | `composer.json` only | `module.json` + `composer.json` |
| **Cache type** | Persistent file cache | In-memory only (per request) |
| **Service providers** | DeferrableProvider (lazy) | Eager loading |
| **Enable/disable modules** | Not supported | Supported via JSON file |
| **Architecture pattern** | Domain-driven (Porto-inspired) | Flexible structure |

### Performance Impact

#### Production (with cache enabled)

| Metric | Laraneat | nWidart |
|--------|----------|---------|
| File operations (first request) | 1 (cached manifest) | N (module.json × modules) |
| File operations (subsequent) | 1 | N |
| Providers loaded | On-demand | All modules |

#### Development (without cache)

Both packages scan the filesystem on each request. However, Laraneat uses `DeferrableProvider`, so the `ModulesRepository` is only instantiated when actually needed.

### Why Laraneat is Faster

1. **Persistent manifest cache** — Module metadata is cached to `bootstrap/cache/laraneat-modules.php`, eliminating filesystem scans in production.

2. **DeferrableProvider** — Core services (`ModulesRepository`, `Composer`, console commands) implement Laravel's `DeferrableProvider` interface, loading only when requested.

3. **Single manifest file** — Uses existing `composer.json` instead of requiring an additional `module.json` per module.

4. **No status file I/O** — No `modules_statuses.json` reads on every request (unlike nWidart's enabled/disabled feature).

### Recommendations

```php
// config/modules.php - Enable cache in production
'cache' => [
    'enabled' => env('APP_ENV') === 'production',
],
```

After deployment:
```bash
php artisan module:cache
```

For development with many modules, consider enabling cache manually to avoid repeated filesystem scans.

## Architecture Diagram

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                           LARAVEL APPLICATION                                │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │                       ModulesRepository                              │   │
│  │  - Discovers modules by scanning configured paths                    │   │
│  │  - Manages module manifest (cached in production)                    │   │
│  │  - Provides find, filter, delete operations                          │   │
│  └──────────────────────────────┬──────────────────────────────────────┘   │
│                                 │                                           │
│                    ┌────────────┼────────────┐                             │
│                    │            │            │                             │
│                    ▼            ▼            ▼                             │
│  ┌─────────────────────┐ ┌─────────────────────┐ ┌─────────────────────┐   │
│  │   MODULE: Users     │ │  MODULE: Articles   │ │  MODULE: Orders     │   │
│  │                     │ │                     │ │                     │   │
│  │  ┌───────────────┐  │ │  ┌───────────────┐  │ │  ┌───────────────┐  │   │
│  │  │  composer.json│  │ │  │  composer.json│  │ │  │  composer.json│  │   │
│  │  │  - providers  │  │ │  │  - providers  │  │ │  │  - providers  │  │   │
│  │  │  - aliases    │  │ │  │  - aliases    │  │ │  │  - aliases    │  │   │
│  │  │  - namespace  │  │ │  │  - namespace  │  │ │  │  - namespace  │  │   │
│  │  └───────────────┘  │ │  └───────────────┘  │ │  └───────────────┘  │   │
│  │                     │ │                     │ │                     │   │
│  │  src/               │ │  src/               │ │  src/               │   │
│  │  ├── Actions/       │ │  ├── Actions/       │ │  ├── Actions/       │   │
│  │  ├── Models/        │ │  ├── Models/        │ │  ├── Models/        │   │
│  │  ├── Providers/     │ │  ├── Providers/     │ │  ├── Providers/     │   │
│  │  └── UI/            │ │  └── UI/            │ │  └── UI/            │   │
│  │      ├── API/       │ │      ├── API/       │ │      ├── API/       │   │
│  │      ├── WEB/       │ │      ├── WEB/       │ │      ├── WEB/       │   │
│  │      └── CLI/       │ │      └── CLI/       │ │      └── CLI/       │   │
│  │                     │ │                     │ │                     │   │
│  │  database/          │ │  database/          │ │  database/          │   │
│  │  └── migrations/    │ │  └── migrations/    │ │  └── migrations/    │   │
│  │                     │ │                     │ │                     │   │
│  │  tests/             │ │  tests/             │ │  tests/             │   │
│  └─────────────────────┘ └─────────────────────┘ └─────────────────────┘   │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

### Module Internal Architecture

```
┌────────────────────────────────────────────────────────────────────────┐
│                              MODULE                                     │
├────────────────────────────────────────────────────────────────────────┤
│                                                                        │
│  ┌──────────────────────────── UI LAYER ────────────────────────────┐  │
│  │                                                                   │  │
│  │  ┌─────────────┐    ┌─────────────┐    ┌─────────────┐          │  │
│  │  │     API     │    │     WEB     │    │     CLI     │          │  │
│  │  │ Controllers │    │ Controllers │    │  Commands   │          │  │
│  │  │  Requests   │    │  Requests   │    │             │          │  │
│  │  │  Resources  │    │   Views     │    │             │          │  │
│  │  │   Routes    │    │   Routes    │    │             │          │  │
│  │  └──────┬──────┘    └──────┬──────┘    └──────┬──────┘          │  │
│  │         │                  │                  │                  │  │
│  └─────────┼──────────────────┼──────────────────┼──────────────────┘  │
│            │                  │                  │                     │
│            └──────────────────┼──────────────────┘                     │
│                               ▼                                        │
│  ┌────────────────────── DOMAIN LAYER ──────────────────────────────┐  │
│  │                                                                   │  │
│  │  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐               │  │
│  │  │   Actions   │  │    DTOs     │  │   Events    │               │  │
│  │  │ (Business   │  │  (Data      │  │ (Domain     │               │  │
│  │  │  Logic)     │  │  Transfer)  │  │  Events)    │               │  │
│  │  └──────┬──────┘  └─────────────┘  └─────────────┘               │  │
│  │         │                                                         │  │
│  │         ▼                                                         │  │
│  │  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐               │  │
│  │  │   Models    │  │  Observers  │  │   Rules     │               │  │
│  │  │ (Entities)  │  │             │  │(Validation) │               │  │
│  │  └─────────────┘  └─────────────┘  └─────────────┘               │  │
│  │                                                                   │  │
│  └───────────────────────────────────────────────────────────────────┘  │
│                                                                        │
│  ┌─────────────────── INFRASTRUCTURE LAYER ─────────────────────────┐  │
│  │                                                                   │  │
│  │  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐               │  │
│  │  │  Providers  │  │ Middleware  │  │  Policies   │               │  │
│  │  └─────────────┘  └─────────────┘  └─────────────┘               │  │
│  │                                                                   │  │
│  │  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐               │  │
│  │  │    Mails    │  │Notifications│  │    Jobs     │               │  │
│  │  └─────────────┘  └─────────────┘  └─────────────┘               │  │
│  │                                                                   │  │
│  └───────────────────────────────────────────────────────────────────┘  │
│                                                                        │
│  ┌──────────────────── DATABASE LAYER ──────────────────────────────┐  │
│  │                                                                   │  │
│  │  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐               │  │
│  │  │ Migrations  │  │   Seeders   │  │  Factories  │               │  │
│  │  └─────────────┘  └─────────────┘  └─────────────┘               │  │
│  │                                                                   │  │
│  └───────────────────────────────────────────────────────────────────┘  │
│                                                                        │
└────────────────────────────────────────────────────────────────────────┘
```

### Request Flow Through Module

Actions serve as controllers using the `lorisleiva/laravel-actions` package. Each Action has two entry points:
- `handle()` - Core business logic (can be called from anywhere)
- `asController()` - HTTP entry point (receives Request, returns Response)

```
┌──────────┐     ┌───────────┐     ┌────────────┐
│  HTTP    │────▶│  Routes   │────▶│ Middleware │
│ Request  │     │           │     │            │
└──────────┘     └───────────┘     └─────┬──────┘
                                         │
                 ┌───────────────────────┘
                 ▼
┌─────────────────────────────────────────────────────────────────┐
│                         ACTION                                   │
│  ┌─────────────────────────────────────────────────────────┐    │
│  │  asController(Request $request)     ◀── HTTP Entry      │    │
│  │    │                                                     │    │
│  │    ├── $request->toDTO()  ─────────▶  DTO               │    │
│  │    │                                                     │    │
│  │    └── $this->handle($dto)                              │    │
│  └────────────────────────┬────────────────────────────────┘    │
│                           ▼                                      │
│  ┌─────────────────────────────────────────────────────────┐    │
│  │  handle(DTO $dto)                   ◀── Business Logic  │    │
│  │    │                                                     │    │
│  │    └── Model::create($dto->all())  ──▶  Database        │    │
│  └────────────────────────┬────────────────────────────────┘    │
│                           │                                      │
└───────────────────────────┼──────────────────────────────────────┘
                            ▼
                 ┌────────────────┐     ┌─────────────┐
                 │    Resource    │────▶│    HTTP     │
                 │   (Format)     │     │  Response   │
                 └────────────────┘     └─────────────┘
```

**Example Action:**

```php
class CreatePostAction
{
    use AsAction;

    // Business logic - can be called from anywhere
    public function handle(CreatePostDTO $dto): Post
    {
        return Post::create($dto->all());
    }

    // HTTP entry point - acts as controller
    public function asController(CreatePostRequest $request): JsonResponse
    {
        $post = $this->handle($request->toDTO());

        return (new PostResource($post))->created();
    }
}
```

## Installation

```bash
composer require laraneat/modules
```

Publish the configuration file:

```bash
php artisan vendor:publish --provider="Laraneat\Modules\Providers\ModulesServiceProvider"
```

## Quick Start

### 1. Create Your First Module

```bash
php artisan module:make Blog
```

This creates a new module at `modules/blog/` with basic structure.

### 2. Create Module with Full API

```bash
php artisan module:make Blog --preset=api --entity=Post
```

This creates a complete REST API module with:
- Controllers for CRUD operations
- Request validation classes
- API resources for JSON responses
- Database migrations and seeders
- Complete test suite

### 3. Generate Components

```bash
# Create a model
php artisan module:make:model Post app/blog

# Create a controller
php artisan module:make:controller PostController app/blog --ui=api

# Create a migration
php artisan module:make:migration create_posts_table app/blog

# Create an action
php artisan module:make:action CreatePostAction app/blog
```

### 4. Run Module Migrations

```bash
php artisan module:migrate
```

## Module Structure

A complete module follows this structure:

```
modules/blog/
├── composer.json              # Module package definition
├── src/
│   ├── Actions/               # Business logic actions
│   │   ├── CreatePostAction.php
│   │   ├── UpdatePostAction.php
│   │   └── DeletePostAction.php
│   │
│   ├── Models/                # Eloquent models
│   │   └── Post.php
│   │
│   ├── DTO/                   # Data Transfer Objects
│   │   ├── CreatePostDTO.php
│   │   └── UpdatePostDTO.php
│   │
│   ├── Events/                # Domain events
│   │   └── PostCreated.php
│   │
│   ├── Listeners/             # Event listeners
│   │   └── SendPostNotification.php
│   │
│   ├── Jobs/                  # Queued jobs
│   │   └── ProcessPost.php
│   │
│   ├── Policies/              # Authorization policies
│   │   └── PostPolicy.php
│   │
│   ├── Providers/             # Service providers
│   │   ├── BlogServiceProvider.php
│   │   └── RouteServiceProvider.php
│   │
│   └── UI/
│       ├── API/
│       │   ├── Controllers/
│       │   │   └── PostController.php
│       │   ├── Requests/
│       │   │   ├── CreatePostRequest.php
│       │   │   └── UpdatePostRequest.php
│       │   ├── Resources/
│       │   │   └── PostResource.php
│       │   ├── QueryWizards/
│       │   │   └── PostsQueryWizard.php
│       │   └── routes/
│       │       └── v1.php
│       │
│       ├── WEB/
│       │   ├── Controllers/
│       │   ├── Requests/
│       │   └── routes/
│       │
│       └── CLI/
│           └── Commands/
│
├── database/
│   ├── migrations/
│   │   └── 2024_01_01_create_posts_table.php
│   ├── seeders/
│   │   └── PostSeeder.php
│   └── factories/
│       └── PostFactory.php
│
├── resources/
│   └── views/
│
├── lang/
│
├── config/
│
└── tests/
    ├── Unit/
    ├── Feature/
    └── API/
```

## Core Concepts

### Module

A **Module** represents a self-contained business domain. It's identified by its Composer package name (e.g., `app/blog`).

```php
use Laraneat\Modules\ModulesRepository;

$repository = app(ModulesRepository::class);

// Find a module
$module = $repository->find('app/blog');

// Get module properties
$module->getName();           // "blog"
$module->getStudlyName();     // "Blog"
$module->getNamespace();      // "Modules\Blog"
$module->getPath();           // "/path/to/modules/blog"
$module->getProviders();      // ["Modules\Blog\Providers\BlogServiceProvider"]
```

### ModulesRepository

The **ModulesRepository** discovers and manages all modules in your application.

```php
use Laraneat\Modules\ModulesRepository;

$repository = app(ModulesRepository::class);

// Get all modules
$modules = $repository->getModules();

// Check if module exists
$repository->has('app/blog');

// Find module by name
$repository->filterByName('Blog');

// Delete a module
$repository->delete('app/blog');
```

### Actions

**Actions** are the core of the architecture. Using `lorisleiva/laravel-actions`, they serve dual purposes:
- **Business Logic** via `handle()` method - can be called from anywhere (other actions, jobs, commands)
- **HTTP Controller** via `asController()` method - handles HTTP requests directly

```php
// src/Actions/CreatePostAction.php
namespace Modules\Blog\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Blog\DTO\CreatePostDTO;
use Modules\Blog\Models\Post;
use Modules\Blog\UI\API\Requests\CreatePostRequest;
use Modules\Blog\UI\API\Resources\PostResource;
use Illuminate\Http\JsonResponse;

class CreatePostAction
{
    use AsAction;

    // Core business logic - reusable from anywhere
    public function handle(CreatePostDTO $dto): Post
    {
        return Post::create($dto->all());
    }

    // HTTP entry point - acts as controller
    public function asController(CreatePostRequest $request): JsonResponse
    {
        $post = $this->handle($request->toDTO());

        return (new PostResource($post))->created();
    }
}
```

**Routes point directly to Actions:**

```php
// routes/v1.php
Route::post('/posts', CreatePostAction::class);
Route::get('/posts', ListPostsAction::class);
Route::get('/posts/{post}', ViewPostAction::class);
Route::put('/posts/{post}', UpdatePostAction::class);
Route::delete('/posts/{post}', DeletePostAction::class);
```

### DTOs (Data Transfer Objects)

**DTOs** are simple objects that carry data between layers.

```php
// src/DTO/CreatePostDTO.php
namespace Modules\Blog\DTO;

class CreatePostDTO
{
    public function __construct(
        public readonly string $title,
        public readonly string $content,
        public readonly int $authorId,
    ) {}

    public static function fromRequest(CreatePostRequest $request): self
    {
        return new self(
            title: $request->validated('title'),
            content: $request->validated('content'),
            authorId: $request->user()->id,
        );
    }
}
```

### Module Service Provider

Each module has a service provider that extends `ModuleServiceProvider`:

```php
// src/Providers/BlogServiceProvider.php
namespace Modules\Blog\Providers;

use Laraneat\Modules\Support\ModuleServiceProvider;

class BlogServiceProvider extends ModuleServiceProvider
{
    public function boot(): void
    {
        // Load module commands
        $this->loadCommandsFrom([
            'Modules\\Blog\\UI\\CLI\\Commands' => __DIR__ . '/../UI/CLI/Commands',
        ]);

        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        // Load views
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'blog');
    }
}
```

## Configuration

After publishing, edit `config/modules.php`:

```php
return [
    // Where modules are stored
    'path' => base_path('modules'),

    // Base namespace for all modules
    'namespace' => 'Modules',

    // Custom stubs location (optional)
    'custom_stubs' => base_path('stubs/modules'),

    // Composer settings for generated modules
    'composer' => [
        'vendor' => 'app',
        'author' => [
            'name' => 'Your Name',
            'email' => 'your@email.com',
        ],
    ],

    // Component path/namespace mappings
    'components' => [
        'action' => [
            'path' => 'src/Actions',
            'namespace' => 'Actions',
        ],
        'model' => [
            'path' => 'src/Models',
            'namespace' => 'Models',
        ],
        // ... more components
    ],

    // Enable manifest caching (recommended for production)
    'cache' => [
        'enabled' => env('APP_ENV') === 'production',
    ],
];
```

## Artisan Commands

### Module Management

| Command | Description |
|---------|-------------|
| `module:list` | Display all registered modules |
| `module:sync` | Refresh manifest and sync with Composer |
| `module:delete {package}` | Delete a module completely |
| `module:cache` | Build module manifest cache |
| `module:cache:clear` | Clear module manifest cache |

### Module Creation

```bash
# Create module with interactive preset selection
php artisan module:make Blog

# Create with specific preset
php artisan module:make Blog --preset=api --entity=Post
```

### Component Generators

| Command | Description |
|---------|-------------|
| `module:make:action` | Create an Action class |
| `module:make:controller` | Create a Controller (--ui=api\|web) |
| `module:make:model` | Create an Eloquent Model |
| `module:make:migration` | Create a database migration |
| `module:make:request` | Create a Form Request |
| `module:make:resource` | Create an API Resource |
| `module:make:dto` | Create a DTO class |
| `module:make:event` | Create an Event class |
| `module:make:listener` | Create an Event Listener |
| `module:make:job` | Create a Job class |
| `module:make:policy` | Create a Policy class |
| `module:make:provider` | Create a Service Provider |
| `module:make:middleware` | Create Middleware |
| `module:make:command` | Create a Console Command |
| `module:make:factory` | Create a Model Factory |
| `module:make:seeder` | Create a Database Seeder |
| `module:make:test` | Create a Test class |
| `module:make:observer` | Create a Model Observer |
| `module:make:notification` | Create a Notification |
| `module:make:mail` | Create a Mailable |
| `module:make:rule` | Create a Validation Rule |
| `module:make:query-wizard` | Create a QueryWizard |
| `module:make:route` | Create a Route file |
| `module:make:exception` | Create an Exception class |

### Migration Commands

| Command | Description |
|---------|-------------|
| `module:migrate` | Run module migrations |
| `module:migrate:rollback` | Rollback module migrations |
| `module:migrate:reset` | Reset all module migrations |
| `module:migrate:refresh` | Refresh module migrations |
| `module:migrate:status` | Show migration status |

## Component Types

The package supports 30+ component types organized by architectural layers:

### UI Layer

**API Components:**
- `ApiController` - REST API controllers
- `ApiRequest` - API form requests
- `ApiResource` - API JSON resources
- `ApiRoute` - API route files
- `ApiQueryWizard` - Query builder wrappers
- `ApiTest` - API integration tests

**WEB Components:**
- `WebController` - Web controllers
- `WebRequest` - Web form requests
- `WebRoute` - Web route files
- `WebTest` - Web integration tests

**CLI Components:**
- `CliCommand` - Artisan commands
- `CliTest` - Command tests

### Domain Layer

- `Action` - Business logic actions
- `Model` - Eloquent models
- `Dto` - Data Transfer Objects
- `Event` - Domain events
- `Listener` - Event listeners
- `Job` - Queued jobs
- `Rule` - Validation rules
- `Observer` - Model observers

### Infrastructure Layer

- `Provider` - Service providers
- `Middleware` - HTTP middleware
- `Policy` - Authorization policies
- `Mail` - Mailable classes
- `Notification` - Notifications

### Database Layer

- `Migration` - Database migrations
- `Seeder` - Database seeders
- `Factory` - Model factories

## Module Presets

### Plain Preset (Default)

Basic module with minimal structure:
- Service providers only
- Empty directory structure

```bash
php artisan module:make Blog --preset=plain
```

### Base Preset

Includes database layer components:
- Model with migrations
- Factory for testing
- Seeder with permissions
- Authorization policy

```bash
php artisan module:make Blog --preset=base --entity=Post
```

### API Preset

Complete REST API module:
- All base preset components
- CRUD controllers
- Form requests (create, update, delete, list, view)
- API resources
- QueryWizard for filtering/sorting
- DTOs for data transfer
- Complete route file
- Full test coverage

```bash
php artisan module:make Blog --preset=api --entity=Post
```

## Best Practices

### 1. Keep Modules Independent

Modules should be loosely coupled. If module A depends on module B, consider:
- Using events for communication
- Creating shared interfaces
- Moving shared code to a separate package

### 2. Separate HTTP Logic from Business Logic

Keep `asController()` thin - it should only handle HTTP concerns. Put business logic in `handle()`:

```php
class CreatePostAction
{
    use AsAction;

    // Business logic - reusable, testable
    public function handle(CreatePostDTO $dto): Post
    {
        $post = Post::create($dto->all());
        event(new PostCreated($post));

        return $post;
    }

    // HTTP concerns only - request/response handling
    public function asController(CreatePostRequest $request): JsonResponse
    {
        $post = $this->handle($request->toDTO());

        return (new PostResource($post))->created();
    }
}
```

### 3. Reuse Actions Across Contexts

Actions can be called from multiple places:

```php
// From another Action
class ImportPostsAction
{
    public function __construct(private CreatePostAction $createPost) {}

    public function handle(array $posts): void
    {
        foreach ($posts as $postData) {
            $this->createPost->handle(new CreatePostDTO(...$postData));
        }
    }
}

// From a Job
class ProcessImportJob implements ShouldQueue
{
    public function handle(CreatePostAction $action): void
    {
        $action->handle($this->dto);
    }
}

// From a Command
class SeedPostsCommand extends Command
{
    public function handle(CreatePostAction $action): void
    {
        $action->handle(new CreatePostDTO(...));
    }
}
```

### 4. Use DTOs for Data Transfer

DTOs provide type safety and clear contracts:

```php
class CreatePostDTO
{
    public function __construct(
        public readonly string $title,
        public readonly string $content,
        public readonly int $authorId,
    ) {}

    public function all(): array
    {
        return [
            'title' => $this->title,
            'content' => $this->content,
            'author_id' => $this->authorId,
        ];
    }
}
```

### 5. Organize Routes by Version

For APIs, version your routes:

```
routes/
├── v1.php    # Version 1 routes
└── v2.php    # Version 2 routes
```

### 6. Write Tests

Each module should have comprehensive tests:

```bash
# Run all module tests
./vendor/bin/pest modules/blog/tests

# Run specific test
./vendor/bin/pest --filter "can create post"
```

### 7. Use Caching in Production

Enable manifest caching for better performance:

```php
// config/modules.php
'cache' => [
    'enabled' => env('APP_ENV') === 'production',
],
```

Run after deployment:
```bash
php artisan module:cache
```

## License

MIT License. See [LICENSE](LICENSE) for details.
