<?php

declare(strict_types=1);

namespace Atom\Modules\Accounts;

use Atom\Http\ParameterCollection;
use SensitiveParameter;

final readonly class RegisterAccount
{
    private ParameterCollection $fields;

    /**
     * @param array<string, mixed> $fields
     */
    public function __construct(
        public string $login,
        #[SensitiveParameter] private string $password,
        array $fields = []
    ) {
        $this->fields = ParameterCollection::from($fields);
    }

    public function password(): string
    {
        return $this->password;
    }

    public function has(string $name): bool
    {
        return $this->fields->has($name);
    }

    public function get(string $name, mixed $default = null): mixed
    {
        return $this->fields->get($name, $default);
    }

    public function string(string $name, string $default = ""): string
    {
        return $this->fields->string($name, $default);
    }

    public function int(string $name, int $default = 0): int
    {
        return $this->fields->int($name, $default);
    }

    public function bool(string $name, bool $default = false): bool
    {
        return $this->fields->bool($name, $default);
    }

    /**
     * @return array<string, mixed>
     */
    public function fields(): array
    {
        return $this->fields->toArray();
    }
}
