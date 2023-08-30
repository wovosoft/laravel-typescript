## Story

```php
### First we need to get the model classes in a directory

use Wovosoft\LaravelTypescript\Helpers;

$modelClasses = Helpers\Models::in(app_path('Models'));



### Then, these models are passed to the generator

$contents = Helpers\Transformer::generate($modelClasses)

```

### What happens in `Helpers\Transfer::generate` method

Generates a collection of provided models including attributes, columns and relations
in a parsable format.



