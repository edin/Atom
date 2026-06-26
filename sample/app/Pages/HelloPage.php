<?php

declare(strict_types=1);

namespace App\Pages;

use Atom\Page\PageRoute;

#[PageRoute("/hello-page", name: "hello.page")]
final class HelloPage extends AppPage
{
    public string $title = "Hello from Atom Page";

    public string $message = "";

    public function get(): void
    {
        $this->message = "Routing and atom.html rendering work.";
    }
}
