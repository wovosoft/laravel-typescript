# Laravel Typescript

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Total Downloads][ico-downloads]][link-downloads]
[![Build Status][ico-travis]][link-travis]
[![StyleCI][ico-styleci]][link-styleci]

Transforms Laravel Models to Typescript Interfaces/Types

## Installation

Via Composer

``` bash
composer require --dev wovosoft/laravel-typescript
```

## Publish Configuration

Run the command given below. This will publish `laravel-typescript.php` config file.

```bash
php artisan vendor:publish --provider="Wovosoft\LaravelTypescript\LaravelTypescriptServiceProvider"
```

Configure the configurations

```php
return [
    'output_path'       => resource_path('js/types/models.d.ts'),
    'source_dir'        => app_path('Models'),
    /**
     * Custom attributes should have return types defined.
     * But if it is not, then the return type should be this type.
     * And this value should be php supported return types.
     * like primitive types or any other classes
     */
    "custom_attributes" => [
        "fallback_return_type" => "string"
    ]
];
```

## Usage

Run the command given below to generate typescript types.

```bash
php artisan laravel-typescript:transform
```

Generated contents will be written in configured location.

## Advanced Usage

Sometimes Models can be stored in different locations, like in some packages, some directories etc.,
in that case, please check the source of
[./src/LaravelTypescript.php](https://github.com/wovosoft/laravel-typescript/blob/master/src/LaravelTypescript.php)

You can just instantiate this class, and generate types for models in some other directories.

```php
use Wovosoft\LaravelTypescript\Facades\LaravelTypescript;


$dirs = [
    "models"               => app_path("Models"),
    "hrmPerson"            => base_path("packages/wovosoft/hrms-person/src/Models"),
];

foreach ($dirs as $name => $dir) {
    LaravelTypescript::generate(
        sourceDir : $dir,
        outputPath: resource_path("js/types/$name.d.ts")
    );
    
    echo "Generated $name.d.ts";
}
```

## Note on New Model Attributes

For new Model Attributes, return type of the Closure function should be defined,
otherwise, it will generate value of
`config('laravel-typescript.custom_attributes.fallback_return_type')` type for the related
property.


```php
use \Illuminate\Database\Eloquent\Model;
use \Illuminate\Database\Eloquent\Casts\Attribute;

class User extends Model{
    public function isActive() : Attribute 
    {
        return Attribute::get(fn(): bool =>$this->status==='active');
    }
    
    public function getIsInactiveAttribute():bool
    {
        return $this->status==="inactive";
    }
}
```

## Change log

Please see the [changelog](changelog.md) for more information on what has changed recently.

## Contributing

Please see [contributing.md](contributing.md) for details and a todolist.

## Security

If you discover any security related issues, please create issues
in [Issues Tracker](https://github.com/wovosoft/laravel-typescript/issues)

## Credits

- [Narayan Adhikary][link-author]
- [All Contributors][link-contributors]

## License

MIT. Please see the [license file](license.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/wovosoft/laravel-typescript.svg?style=flat-square

[ico-downloads]: https://img.shields.io/packagist/dt/wovosoft/laravel-typescript.svg?style=flat-square

[ico-travis]: https://img.shields.io/travis/wovosoft/laravel-typescript/master.svg?style=flat-square

[ico-styleci]: https://github.styleci.io/repos/661637738/shield?branch=master

[link-packagist]: https://packagist.org/packages/wovosoft/laravel-typescript

[link-downloads]: https://packagist.org/packages/wovosoft/laravel-typescript

[link-travis]: https://travis-ci.org/wovosoft/laravel-typescript

[link-styleci]: https://github.styleci.io/repos/661637738

[link-author]: https://github.com/wovosoft

[link-contributors]: ../../contributors
