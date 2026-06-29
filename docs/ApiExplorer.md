# API Explorer

[Atom Framework](Index.md)

Atom's API description layer starts as a model builder for REST APIs. It reads registered routes and reflects controller actions, closures, DTOs, source attributes, and validation attributes.

The first layer is intentionally UI-free:

```php
use Atom\Api\ApiModelBuilder;

$description = (new ApiModelBuilder())->describe($router);
```

Response shape attributes also live in the core API namespace:

```php
use Atom\Api\Attributes\ArrayOf;
use Atom\Api\Attributes\ErrorResponse;
use Atom\Api\Attributes\ResponseOf;
```

Atom can also register a read-only HTML explorer:

```php
use Atom\Modules\ApiExplorer\ApiExplorer;

$this->registerModule(ApiExplorer::module("/atom/api"));
```

This adds a module page that renders the generated API model as a PHP-built developer page. The module root redirects to the page URL:

```text
/atom/api/explorer
```

The module serves its CSS from:

```text
/atom/api/resources/api-explorer.css
```

It also registers the shared Atom browser runtime from the framework module:

```text
/atom/framework/resources/atom.js
```

Use `ResponseOf` with wrapper response models when the action returns a container such as a paged response:

```php
use Atom\Api\Attributes\ArrayOf;
use Atom\Api\Attributes\ResponseOf;

#[ResponseOf(ArticleResponse::class)]
public function articles(): PagedResponse
{
    return new PagedResponse();
}

final class PagedResponse
{
    #[ArrayOf]
    public array $items = [];

    public int $total;
    public int $page;
}
```

Use `#[ArrayOf(SomeResponse::class)]` when the array item type is concrete and does not depend on the action response.

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
