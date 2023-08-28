# Simple Repository

[`PHP v8.0`](https://php.net)

[`Laravel v10`](https://github.com/laravel/laravel)

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
