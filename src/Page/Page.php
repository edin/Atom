<?php

declare(strict_types=1);

namespace Atom\Page;

use Atom\Router\Route;
use Atom\Router\RouteEntry;
use Atom\Validation\ValidationResult;
use Atom\Validation\Validator;
use RuntimeException;

abstract class Page
{
    public ?string $layout = null;
    private ?ValidationResult $validation = null;

    /**
     * @return RouteEntry[]
     */
    public static function registerPages(?string $directory = null): array
    {
        return (new PageRouteRegistrar())->registerDirectory(Route::getRouter(), $directory ?? self::defaultPageDirectory());
    }

    private static function defaultPageDirectory(): string
    {
        $callerFile = null;
        foreach (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS) as $frame) {
            $file = $frame["file"] ?? null;
            if (is_string($file) && realpath($file) !== realpath(__FILE__)) {
                $callerFile = $file;
                break;
            }
        }

        if ($callerFile === null) {
            throw new RuntimeException("Cannot infer page directory. Pass it explicitly to Page::registerPages().");
        }

        return dirname($callerFile) . DIRECTORY_SEPARATOR . "Pages";
    }

    public function template(): ?string
    {
        return null;
    }

    public function validate(): bool
    {
        $this->validation = Validator::for(static::class)->validate($this);

        return $this->validation->passed();
    }

    protected function validateModel(array|object $model, string|null $className = null): bool
    {
        $this->validation = Validator::for($className ?? (is_object($model) ? $model::class : null))->validate($model);

        return $this->validation->passed();
    }

    public function errors(): ValidationResult
    {
        return $this->validation ??= ValidationResult::valid();
    }

    protected function setValidation(ValidationResult $validation): void
    {
        $this->validation = $validation;
    }
}
