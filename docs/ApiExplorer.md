# API Explorer

[Atom Framework](Index.md)

The API explorer starts as a model builder for REST APIs. It reads registered routes and reflects controller actions, closures, DTOs, source attributes, and validation attributes.

The first layer is intentionally UI-free:

```php
use Atom\ApiExplorer\ApiModelBuilder;

$description = (new ApiModelBuilder())->describe($router);
```

Atom can also register a read-only HTML explorer:

```php
use Atom\ApiExplorer\ApiExplorer;

$this->registerModule(ApiExplorer::module("/atom/api"));
```

This adds a `GET` route that renders the generated API model as a PHP-built developer page.

The module serves its CSS from:

```text
/atom/api/resources/api-explorer.css
```

It also registers the shared Atom browser runtime from the framework module:

```text
/atom/framework/resources/atom.js
```

The module includes a UI preview page with fixed sample data:

```text
/atom/api/preview
```

The preview page is built with Atom module pages, components, and resources. Use it to iterate on the explorer layout before wiring the same components into the live API model.

`ApiDescription` contains endpoint descriptors:

```php
$description->endpoints[0]->methods;
$description->endpoints[0]->path;
$description->endpoints[0]->handler;
$description->endpoints[0]->requestFields;
$description->endpoints[0]->responseType;
```

## Request Fields

Action scalar parameters become fields. Route parameters are inferred from paths such as:

```text
/api/users/{id}
```

DTO parameters marked with `#[Dto]` are expanded into property fields:

```php
use Atom\Hydrator\Attributes\Dto;
use Atom\Hydrator\Attributes\FromBody;
use Atom\Hydrator\Attributes\FromQuery;
use Atom\Validation\Rules\Required;

#[Dto]
final class CreateUserDto
{
    #[FromBody]
    #[Required]
    public string $name;

    #[FromBody]
    public int $age;

    #[FromQuery("invite")]
    public ?string $inviteCode = null;
}
```

Each field describes:

- source: `auto`, `body`, `query`, `route`, `header`, or `file`
- source name
- PHP type
- whether the field is required
- validation rule classes
- DTO model class, when the field came from a DTO

This model can later drive a browser explorer UI, generated OpenAPI output, or test clients.
