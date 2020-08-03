# Validation

[Atom Framework](Index.md)

## Example

```php
class Model {
    public ?int $Id;
    public string $Title;
    public string $Description;
}
```

Building validator and validating model:

```php
$validation = Validation::create(function (Validation $rule) {
    $rule->field("Title")->required()->trim()->length(1, 100);
    $rule->field("Description")->trim()->maxLength(1000);
});

$model = new Model();
$result = $validation->validate($model);
```

> //TODO: Add more examples