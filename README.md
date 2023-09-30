# Simple Repository

[`PHP v8.0`](https://php.net)

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

You can specify a model that your service depends on during creation by adding option `--model` or `-m`.

```bash
php artisan make:service UserService --model=User
```

#OR

```bash
php artisan make:service UserService -m User
```

You can use a service along with a repository, using the repository instead of the model. Specify the repository your service depends on when creating the service by adding option `--repo` or `-r`.

```bash
php artisan make:service UserService --repo=UserRepository
```

#OR

```bash
php artisan make:service UserService -r UserRepository
```
