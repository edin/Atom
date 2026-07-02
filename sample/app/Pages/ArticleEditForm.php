<?php

declare(strict_types=1);

namespace App\Pages;

use Atom\Validation\Rules\MaxLength;
use Atom\Validation\Rules\Required;

final class ArticleEditForm
{
    #[Required("Give this article a title.")]
    #[MaxLength(120)]
    public string $title = "";

    #[Required("Add a short summary.")]
    #[MaxLength(220)]
    public string $summary = "";
}
