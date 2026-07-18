<?php

declare(strict_types=1);

namespace Atom\Modules\Components;

use Atom\Page\Page;
use Atom\Validation\ValidationError;
use Atom\View\Html;
use Atom\View\Component\ComponentInterface;

final class ValidationSummary implements ComponentInterface
{
    public Page $page;
    public string $class = "atom-validation-summary";
    public string $title = "";
    public string $only = "";
    public string $except = "";

    public function render(): string
    {
        $errors = $this->filteredErrors();
        if ($errors === []) {
            return "";
        }

        $content = "";
        if ($this->title !== "") {
            $content .= Html::tag("p", ["class" => "atom-validation-summary__title"], Html::escape($this->title));
        }

        $items = "";
        foreach ($errors as $error) {
            $items .= Html::tag("li", content: Html::escape($error->message));
        }

        $content .= Html::tag("ul", content: $items);

        return Html::tag("div", ["class" => $this->class], $content);
    }

    /**
     * @return list<ValidationError>
     */
    private function filteredErrors(): array
    {
        $only = $this->fieldList($this->only);
        $except = $this->fieldList($this->except);

        return array_values(array_filter(
            $this->page->errors()->all(),
            static function (ValidationError $error) use ($only, $except): bool {
                if ($only !== [] && !in_array($error->field, $only, true)) {
                    return false;
                }

                return !in_array($error->field, $except, true);
            }
        ));
    }

    /**
     * @return list<string>
     */
    private function fieldList(string $value): array
    {
        if (trim($value) === "") {
            return [];
        }

        return array_values(array_filter(
            array_map(static fn(string $field): string => trim($field), explode(",", $value)),
            static fn(string $field): bool => $field !== ""
        ));
    }
}
