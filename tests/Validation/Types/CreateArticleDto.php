<?php

declare(strict_types=1);

namespace Atom\Tests\Validation\Types;

use Atom\Validation\Rules\In;
use Atom\Validation\Rules\MaxLength;
use Atom\Validation\Rules\Min;
use Atom\Validation\Rules\Numeric;
use Atom\Validation\Rules\Required;

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

    #[Numeric]
    #[Min(1)]
    public int|string|null $categoryId = null;
}

