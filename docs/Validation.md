# Validation

[Atom Framework](Index.md)

Validation supports two styles: fluent schemas for manual validation and attributes for DTO validation.

## Fluent Schema

```php
use Atom\Validation\Schema;

$schema = Schema::make()
    ->field("title")->required()->maxLength(120)
    ->field("body")->required()->minLength(10)
    ->field("status")->required()->in(["draft", "published"])
    ->schema();

$result = $schema->validate([
    "title" => "",
    "body" => "Short",
    "status" => "archived",
]);

if ($result->failed()) {
    $message = $result->first("title");
}
```

## Attribute Validation

```php
use Atom\Validation\Rules\In;
use Atom\Validation\Rules\MaxLength;
use Atom\Validation\Rules\Required;
use Atom\Validation\Validator;

final class CreateArticleDto
{
    #[Required]
    #[MaxLength(120)]
    public string $title = "";

    #[Required]
    public string $body = "";

    #[Required]
    #[In(["draft", "published"])]
    public string $status = "draft";
}

$result = Validator::for(CreateArticleDto::class)->validate($dto);
```

Validation errors are grouped by field:

```php
$result->passed();
$result->failed();
$result->hasErrorsFor("title");
$result->first("title");
$result->messages();
```

