# Simple Repository

[`PHP v8.2`](https://php.net)

[`Laravel v11.x`](https://github.com/laravel/laravel)

## Installation

Install using composer:

```bash
composer require ttpn18121996/simple-repository
```

Next, publish SimpleRepository's resources using the `simple-repository:install` command:

```bash
php artisan simple-repository:install
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

Use another repository during the build by adding the `--repo` or `-r` option. For example,
if you want to use an external service instead of Eloquent,
now the repository will be created in the path `app/Repositories/Api/UserRepository.php`

```bash
php artisan make:repository UserRepository --repo Api

#OR

php artisan make:repository UserRepository -r Api
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

The example shows the dynamic extension of the repository pattern.
We use province data in the database.
After a while, we realize that using local data is no longer suitable and we want to use a data source from an external web service.
Editing the existing `Eloquent\ProvinceRepository` content will result in errors or be difficult to revert to before.
Instead, we will create a new repository called `Api\ProvinceRepository` while still ensuring its accuracy like the old repository.

```text
/app
├---/Providers
|   ├---RepositoryServiceProvider.php
├---/Repositories
|   ├---/Api
|   |   ├---ProvinceRepository.php
|   ├---/Contracts
|   |   ├---ProvinceRepository.php
|   ├---/Eloquent
|   |   ├---ProvinceRepository.php
```

app/Repositories/Eloquent/ProvinceRepository.php

```php
<?php

namespace App\Repositories\Eloquent;

use App\Models\Province;
use App\Repositories\Contracts\ProvinceRepository as ProvinceRepositoryContract;

class ProvinceRepository implements ProvinceRepositoryContract
{
    public function getDataSource()
    {
        return new Province();
    }

    public function all()
    {
        return $this->getDataSource()->all();
    }
}
```

app/Repositories/Api/ProvinceRepository.php

```php
<?php

namespace App\Repositories\Api;

use App\Repositories\Contracts\ProvinceRepository as ProvinceRepositoryContract;
use Illuminate\Support\Facades\Http;

class ProvinceRepository implements ProvinceRepositoryContract
{
    public function getDataSource()
    {
        return Http::baseUrl('https://api.domain.example');
    }

    public function all()
    {
        $response = $this->getDataSource()->get('/provinces');

        return $response->success() ? $response->collection() : collect();
    }
}
```

Finally, what we need to do is change the binding between the abstract and the concrete in `RepositoryServiceProvider`

```php
protected $repositories = [
    ...
    // \App\Repositories\Contracts\ProvinceRepository::class => \App\Repositories\Eloquent\ProvinceRepository::class,
    \App\Repositories\Contracts\ProvinceRepository::class => \App\Repositories\Api\ProvinceRepository::class,
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

## Implementation and expansion

The simple repository provides two trait classes "HasFilter" and "Safetyable" that serve to build queries that handle
sorting and filtering of data (HasFilter) and use transactions for data interaction (Safetyable). By default, the base
service and base repository classes extend these two trait classes.

**HasFilter** provides a `buildFilter` method. To use this feature, we need to pass the `filters` parameter in the format:

```php
$this->buildFilter(query: $query, filters: [
    'search' => [ // Relative search (operator "like")
        'field_1' => 'value1',
        'field_2' => 'value2',
    ],
    'or_search' => [ // Relative search (operator "like"). Use the "orWhere" method
        'field_1' => 'value1',
        'field_2' => 'value2',
    ],
    'filter' => [ // Absolute search (operator "=")
        'field_1' => 'value1',
        'field_2' => 'value2',
    ],
    'or_filter' => [ // Absolute search (operator "="). Use the "orWhere" method
        'field_1' => 'value1',
        'field_2' => 'value2',
    ],
    'sort' => [ // Sort data
        'field' => 'field_name',
        'direction' => 'asc' // asc | desc
    ],
    // If the value is null, the records that have not been deleted will be queried.
    // Otherwise, the query will be based on the column name with the value not null.
    'deleted' => 'deleted_at', // by default is null
]);
```

To fix the problem of 2 tables having the same field name or you want to change the field on the url to avoid revealing
the table and field names in the database, the solution is to create a "transferredFields" property in your service class.

For example: users and roles tables both have a field of "name".

```php
namespace App\Services;

class UserService extends Service
{
    protected array $transferredFields = [
        'name' => 'users.name',
        'role_name' => 'roles.name',
    ];
}
```

Or you can also directly override the "getTransferredField" method to transfer the field name.

```php
namespace App\Services;

class UserService extends Service
{
    protected function getTransferredField(string $field): string
    {
        return [
            'name' => 'users.name',
            'role_name' => 'roles.name',
        ][$field] ?? $field;
    }
}
```

## ModelFactory attribute

When using model in service, it will look like this:

```php
<?php

namespace App\Services;

use App\Models\User;

class UserService extends Service
{
    public function __construct(
        public User $user,
    ) {
    }

    public function getById($id)
    {
        return $this->user->find($id);
    }
}
```

Instead of when using a service, dependent models will be initialized and injected automatically through
the container service. Now, only when you call them are they initialized and stored.
You can declare and use the model in the service through the ModelFactory attribute like this:

```php
<?php

namespace App\Services;

use App\Models\User;
use SimpleRepository\Attributes\ModelFactory;

class UserService extends Service
{
    #[ModelFactory(User::class)]
    public ?User $user = null;

    public function getById($id)
    {
        return $this->getModel('user')->find($id);
    }
}
```

## Use database processing functions safely

Instead of like this:

```php
DB::beginTransaction();

try {
    // Do something
    DB::commit();

    return $data;
} catch (\Throwable $e) {
    DB::rollback();
    logger()->error($e->getMessage());

    return null;
}
```

You can use the handleSafely() method instead.
The first parameter is a callback for processing logic, the second parameter is the title of the logging.
Let's say you get an exception, it will be of the form "Title: {message content}"

```php
use SimpleRepository\Concerns\Safetyable;

class MyClass
{
    use Safetyable;

    public function doSomething($params)
    {
        return $this->handleSafely(function () {
            // Do something

            return $data;
        }, 'Do something');
    }
}
```

Services and repositories by default use the Safetyable trait.
You can directly invoke the handleSafely() method within services/repositories.

```php
class UserService extends Service
{
    #[ModelFactory(User::class)]
    protected ?User $user = null;

    public function create(array $data)
    {
        return $this->handleSafely(function () use ($data) {
            $user = $this->getModel('user', $data);
            $user->save();

            return $user;
        }, 'Create user');
    }
}
```

Set up a log channel for the handleSafely() method to log when something goes wrong in the `config/simple-repository.php` file

```php
<?php

return [
    ...
    'log_channel' => 'stack',
];
```

## Tips

Inside the service class, you can call other services with the same namespace without importing them and instantiating
them. You can call them via the `getService` method with the service name as the parameter value. For example,
`App\Services\UserService` wants to use `App\Services\RoleService`.

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

```php
namespace App\Services\UserService;

use App\Services\RoleService;
use SimpleRepository\Attributes\ServiceFactory;

class UserService extends Service
{
    #[ServiceFactory(RoleService::class)]
    public ?RoleService $role = null;

    public function sampleMethod()
    {
        /**
         * @var \App\Services\RoleService
         */
        $roleService = $this->getService('role');
    }
}
```
