# Laravel Actions

An action-object pattern for Laravel. Each domain operation, such as "publish a post" or "assign a role", lives in its own class. That class declares its own validation and authorization, and runs the same way whether it's called from a controller, a console command, a queued job or another action.

## Requirements

- PHP 8.2+
- Laravel 9.0 – 13.x

## Installation

```bash
composer require anasterism/actions
```

## Usage

New actions can be created via the generator command `php artisan make:action posts/PublishPost`.

```php
<?php

namespace App\Actions\Posts;

use App\Models\Post;
use Asterism\Actions\Action;

class PublishPost extends Action
{
    public function handle(): Post
    {
        $post = Post::findOrFail($this->arg('postId'));

        $post->update([
            'published_at' => now()
        ]);

        return $post;
    }
}
```

Actions can be executed via the `execute()` method.

```php
use App\Actions\Posts\PublishPost;

PublishPost::execute(postId: 1);
// OR
PublishPost::execute(['postId' => 1]);
```

## Argument Validation

Argument validation is handled by Laravel's built-in validation system. Action argument validation rules are defined using the optional `rules()` method.

```php
<?php

namespace App\Actions\Posts;

use App\Models\Post;
use Asterism\Actions\Action;

class PublishPost extends Action
{

    protected function rules(): array
    {
        return [
            'postId' => 'required|exists:posts,id'
        ];
    }

    public function handle(): Post
    {
        // ...
    }
}
```

Actions are context-aware. In HTTP contexts, a `ValidationException` is thrown with proper HTTP response. In console contexts, a `InvalidArgumentException` is thrown.

### The `TypeOf` rule

To require an argument to be a particular PHP type or class instance, such as a model, use `TypeOf`:

```php
use App\Models\Post;
use Asterism\Actions\Validation\Rules\TypeOf;

protected function rules(): array
{
    return [
        'post'  => ['required', new TypeOf(Post::class)],
        'limit' => ['nullable', new TypeOf('int')],
    ];
}
```

Supported scalar types are `int`/`integer`, `string`, `bool`/`boolean`, `float`/`double`, `array`, `object` and `null`. Any other value is treated as a class or interface name and checked with `instanceof`.

## Authorization

### The `actor`
A nullable `actor` (`$this->actor`) property is available to every action. The value defaults to the currently authenticated user or can be set explicitly using the `actingAs()` method at the time of execution.

```php
PublishPost::actingAs($user)->execute(postId: 1);
```

Authorization conditions can be defined using the optional `authorize()` method.
```php
<?php

namespace App\Actions\Posts;

use App\Models\Post;
use Asterism\Actions\Action;

class PublishPost extends Action
{

    protected function authorize(): bool
    {
        return $this->actor?->can('posts:publish');
    }

    protected function rules(): array
    {
        return [
            'postId' => 'required|exists:posts,id'
        ];
    }

    public function handle(): Post
    {
        // ...
    }
}
```

In HTTP contexts, authorization is checked automatically before the action is executed. In console commands, it is ignored unless an `actor` is explicitly set at the time of execution.

## Queueing

Using the `dispatch()` method in place of `execute()` runs the action on the queue instead:

```php
SendWeeklyDigest::dispatch(week: $week);
```


## Benchmarking

`enableBenchmark()` logs how long the action took and how much memory it used:

```php
ImportCatalog::enableBenchmark()->execute(file: $path);
```
**Output**:

`storage/logs/actions.log`
```
⚡ BENCHMARK ➔ App\Actions\ImportCatalog ➔ 412.37 ms ➔ 18.52 MB
```

## Conditional Chaining

Actions use Laravel's `Conditional` trait to allow for conditional chaining.

```php
ImportCatalog::when(app()->environment('local'), function (ImportCatalog $action) {
    return $action->enableBenchmark();
})->execute(postId:
```

## Configuration

Publish the config file to change the defaults:

```bash
php artisan vendor:publish --tag=actions-config
```

| Environment variable | Default | Purpose                                       |
| --- | --- |-----------------------------------------------|
| `ACTIONS_LOG_CHANNEL` | `actions` | Log channel used for action benchmark results |
| `ACTIONS_LOG_LEVEL` | `debug` | Level of the default `actions` channel        |
