# Hydration

Atom hydrates request DTOs through `RequestHydrator`. DTOs may use writable properties or constructor parameters, including promoted readonly properties.

## Constructor DTOs

```php
use Atom\Hydrator\Attributes\Dto;
use Atom\Hydrator\Attributes\FromBody;
use Atom\Hydrator\Attributes\FromRoute;

#[Dto]
final readonly class CreateUserDto
{
    public function __construct(
        #[FromRoute]
        public int $accountId,
        #[FromBody("full_name")]
        public string $name,
        #[FromBody]
        public UserRole $role = UserRole::Member
    ) {
    }
}
```

A DTO attribute makes the object directly injectable into a route action:

```php
Route::post("/accounts/{accountId}/users", function (CreateUserDto $dto) {
    // Constructor has already run with coerced request values.
});
```

Constructor parameters support `FromBody`, `FromQuery`, `FromRoute`, `FromHeader`, `FromFile`, `Raw`, and value-transformer attributes such as `DateFormat`. Missing values use declared constructor defaults. An explicitly supplied `null` remains `null` when the type permits it.

## Coercion

The hydrator supports strings, integers, floats, booleans, arrays, mutable and immutable dates, backed enums, unit enums, and nested typed DTOs represented by input arrays. Invalid or missing required values throw `HydrationException` with the class and target name.

When no explicit source attribute is present, lookup checks body, query, and route data in that order. Constructor hydration runs first; remaining writable non-promoted properties are hydrated afterward.

Hydration performs type construction and coercion. Business validation remains explicit through `Validator`, allowing applications to decide when and how validation errors become responses.
