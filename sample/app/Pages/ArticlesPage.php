<?php

declare(strict_types=1);

namespace App\Pages;

use App\Models\Article;
use Atom\Hydrator\Attributes\FromBody;
use Atom\Page\PageAction;
use Atom\Page\PageRoute;
use Atom\Page\State;
use Atom\Validation\Schema;

#[PageRoute("/articles", name: "articles.index")]
final class ArticlesPage extends AppPage
{
    public string $title = "Articles - Atom Sample";

    /** @var list<Article> */
    public array $articles = [];

    #[State]
    public ?int $editingId = null;

    #[State]
    public ?int $deleteId = null;

    /**
     * @var array{id: int, title: string, summary: string}
     */
    #[State]
    public array $edit = [
        "id" => 0,
        "title" => "",
        "summary" => "",
    ];

    public function get(): void
    {
        $this->loadArticles();
    }

    #[PageAction("edit")]
    public function edit(int $id): void
    {
        $article = Article::find($id);
        if (!$article instanceof Article) {
            $this->editingId = null;
            $this->loadArticles();
            return;
        }

        $this->editingId = $article->id;
        $this->deleteId = null;
        $this->edit = [
            "id" => $article->id,
            "title" => $article->title,
            "summary" => $article->summary,
        ];
        $this->loadArticles();
    }

    #[PageAction("cancel")]
    public function cancel(): void
    {
        $this->editingId = null;
        $this->edit = [
            "id" => 0,
            "title" => "",
            "summary" => "",
        ];
        $this->loadArticles();
    }

    #[PageAction("askDelete")]
    public function askDelete(int $id): void
    {
        $article = Article::find($id);
        $this->deleteId = $article instanceof Article ? $article->id : null;
        $this->editingId = null;
        $this->loadArticles();
    }

    #[PageAction("cancelDelete")]
    public function cancelDelete(): void
    {
        $this->deleteId = null;
        $this->loadArticles();
    }

    #[PageAction("deleteConfirmed")]
    public function deleteConfirmed(): void
    {
        if ($this->deleteId !== null) {
            Article::find($this->deleteId)?->delete();
        }

        $this->deleteId = null;
        $this->loadArticles();
    }

    #[PageAction("save")]
    public function save(
        #[FromBody]
        string $title = "",
        #[FromBody]
        string $summary = ""
    ): void
    {
        $this->edit["title"] = $title;
        $this->edit["summary"] = $summary;

        if (!$this->validateEdit()) {
            $this->loadArticles();
            return;
        }

        if ($this->editingId === null) {
            $this->loadArticles();
            return;
        }

        $article = Article::find($this->editingId);
        if (!$article instanceof Article) {
            $this->cancel();
            return;
        }

        $article->title = $this->edit["title"];
        $article->summary = $this->edit["summary"];
        $article->save();

        $this->cancel();
    }

    public function confirmingDelete(): bool
    {
        return $this->deleteId !== null;
    }

    public function deleteTitle(): string
    {
        if ($this->deleteId === null) {
            return "";
        }

        foreach ($this->articles as $article) {
            if ($article->id === $this->deleteId) {
                return $article->title;
            }
        }

        return "";
    }

    private function loadArticles(): void
    {
        $this->articles = Article::query()
            ->where("is_published", true)
            ->orderByDesc("created_at")
            ->with("category")
            ->all();
    }

    private function validateEdit(): bool
    {
        $validation = Schema::make(static function (Schema $schema): void {
            $schema->field("title")
                ->required("Give this article a title.")
                ->maxLength(120);

            $schema->field("summary")
                ->required("Add a short summary.")
                ->maxLength(220);
        })->validate($this->edit);

        $this->setValidation($validation);

        return $validation->passed();
    }
}
