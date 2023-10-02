# Simple Repository

[`PHP v8.1`](https://php.net)

[`Laravel v10`](https://github.com/laravel/laravel)

## Installation

Install using composer:

```bash
composer require ttpn18121996/simple-repository
```

Next, publish SimpleRepository's resources using the `vendor:publish` command:

```bash
php artisan vendor:publish --tag=simple-repository --force
```

Add the service provider in `config/app.php`:

```php
'providers' => [
    ...
    App\Providers\SimpleRepositoryServiceProvider::class,
]
```

## Create repository

Default Repository uses Eloquent, Run command make `app/Repositories/Eloquents/UserRepository.php` file
and interface `app/Repositories/Contracts/UserRepository.php`

```bash
php artisan make:repository UserRepository
```

You can specify a model that your repository depends on during creation by adding options `--model` or `-m`.

```bash
php artisan make:repository UserRepository -m User
```

Using difference repo for `Repository` during creation by adding options `--repo` or `-r`.
For example, don't use Eloquent, instead use the form of getting data from another service through the API.
Now the repository will be created in the directory `app/Repositories/APIs/UserRepository.php`

```bash
php artisan make:repository UserRepository -m User -r APIs
```

After creating the repository remember to declare in `app/Providers/RepositoryServiceProvider.php` where `protected $repositories` (By default they will be added automatically)

```php
protected $repositories = [
    ...
    \App\Repositories\Contracts\UserRepository::class => \App\Repositories\Eloquents\UserRepository::class,
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

You can use repositories instead of models. Specify the repositories your service depends on during creation by adding the --repo or -r option.

```bash
php artisan make:service UserService --repo=UserRepository --repo=RoleRepository

#OR

php artisan make:service UserService -r UserRepository -r RoleRepository
```
