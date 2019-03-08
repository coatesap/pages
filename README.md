# Pages
This package provides the core back end functionality for creating pages and page templates 
within the CMS.

## Usage
This package can be installed through Composer.

`composer require optimuscms/pages`

In Laravel 5.5 and above the package will autoregister the service provider. 

In Laravel 5.4 you must install this service provider:
```php
// config/app.php
'providers' => [
    ...
    Optimus\Pages\PageServiceProvider::class,
    ...
];
```

### Api routes

#### Pages

```http
GET /admin/api/pages
```

```http
POST /admin/api/pages
```

```http
GET /admin/api/pages/{id}
```

```http
PATCH /admin/api/pages/{id}
```

```http
DELETE /admin/api/pages/{id}
```

#### Page templates

```http
GET /admin/api/page-templates
```

### Working with page templates

#### Create a template
```php
use Optimus\Pages\Template;
use Illuminate\Http\Request;
use Optimus\Pages\Models\Page;

class DefaultTemplate extends Template
{
    public function name(): string
    {
        return 'default';
    }
    
    public function label(): string
    {
        return 'A custom label';
    }

    public function validate(Request $request)
    {
        $request->validate([
            'content' => 'required'
        ]);
    }

    public function save(Page $page, Request $request)
    {
        $page->addContents([
            'content' => $request->input('content')
        ]);

        $page->attachMedia($request->input('media_id'));
    }
}
```

#### Register and retrieve templates
```php
use Optimus\Pages\Facades\Template;

// Get all the registered templates...
Template::all();

// Get the template with the given name...
Template::find($name);

// Register a template...
Template::register(new DefaultTemplate);

// Register multiple templates...
Template::registerMany([
    new HomeTemplate,
    new ContactTemplate
]);
```

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
