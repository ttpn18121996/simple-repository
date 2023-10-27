# Simple Repository

[`PHP v8.1`](https://php.net)

[`Laravel v10.x`](https://github.com/laravel/laravel)

## Installation

Install using composer:

```bash
composer require ttpn18121996/simple-repository
```

Next, publish SimpleRepository's resources using the `vendor:publish` command:

```bash
php artisan vendor:publish --tag=simple-repository
```

Add the service provider in `config/app.php`:

```php
'providers' => [
    ...
    App\Providers\SimpleRepositoryServiceProvider::class,
]
```

## Create repository

Default Repository uses Eloquent, Run command make `app/Repositories/Eloquent/UserRepository.php` file
and interface `app/Repositories/Contracts/UserRepository.php`

```bash
php artisan make:repository UserRepository
```

You can specify a model that your repository depends on during creation by adding options `--model` or `-m`.

```bash
php artisan make:repository UserRepository --model=User

#OR

php artisan make:repository UserRepository -m User
```

Use another repository during build by adding the `--repo` or `-r` option. For example, if you want to use Redis instead of Eloquent, now the repository will be created in the path `app/Repositories/Redis/UserRepository.php`

```bash
php artisan make:repository UserRepository -m User -r Redis
```

After creating the repository remember to declare in
`app/Providers/RepositoryServiceProvider.php` where `protected $repositories`
(By default they will be added automatically)

```php
protected $repositories = [
    ...
    \App\Repositories\Contracts\UserRepository::class => \App\Repositories\Eloquent\UserRepository::class,
]
```

## Create service

Run command for make a service. Ex: make `app/Services/UserService.php` file.

```bash
php artisan make:service UserService
```

You can specify the models your service depends on during creation by adding the --model or -m option.

```bash
php artisan make:service UserService --model=User --model=Role

#OR

php artisan make:service UserService -m User -m Role
```

You can use repositories instead of models. Specify the repositories your service depends on during creation
by adding the --repo or -r option.

```bash
php artisan make:service UserService --repo=UserRepository --repo=RoleRepository

#OR

php artisan make:service UserService -r UserRepository -r RoleRepository
```

## Customize the filter builder

Override the `buildFilter` method in the repository class to customize the `buildFilter` from the request.

```php
protected function buildFilter(Builder $query, array $filters = []): Builder
{
    return $query->orderBy('name')
        ->when(Arr::get($filters, 'name'), function (Builder $query, $name) {
            $query->where('name', 'like', "%{$name}%");
        });
}
```

Now you just need to call `getAll` or `getPagination`,
the query will filter itself according to the filters you pass in.

## Customize the relationship builder

Similar to the filter builder, you can override the `buildRelationships` method to customize relational query handling.

```php
protected function buildRelationships(): Builder
{
    return $this->model()->with(['roles', 'permissions']);
}
```

## Customize the query builder

If you want to customize the query without affecting other methods that are using `buildRelationships`
and `buildFilter`, you can override the `getBuilder` method.

```php
protected function getBuilder(array $filters = []): Builder
{
    return $this->model()
        ->with(['roles', 'permissions'])
        ->when(Arr::get($filters, 'name'), function (Builder $query, $name) {
            $query->where('name', 'like', "%{$name}%");
        })
        ->orderBy('name');
}
```

## Set an authenticated user for the service

Use authenticated users to use permission checks in the service.

```php
class UserController
{
    public function index(Request $request)
    {
        $users = $this->userService
            ->useAuthUser($request->user())
            ->getList($request->query());
        ...
    }
}
```

```php
class UserService extends Service
{
    public function getList(array $filters = [])
    {
        if ($this->authUser()->can('view_user')) {
            // Do something
        }
        ...
    }
}
```

## Tips

Inside the service class, you can call other services with the same namespace without importing them and instantiating them. You can call them via the `getService` method with the service name as the parameter value. For example, `App\Services\UserService` wants to use `App\Services\RoleService`.

```php

namespace App\Services\UserService;

public function sampleMethod()
{
    /**
     * @var \App\Services\RoleService
     */
    $roleService = $this->getService('RoleService');
}
```
