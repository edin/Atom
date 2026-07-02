<?php

declare(strict_types=1);

namespace App\Pages;

use App\Models\Article;
use Atom\Page\FormModel;
use Atom\Page\PageAction;
use Atom\Page\PageRoute;
use Atom\Page\State;

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

    #[State]
    #[FormModel]
    public ArticleEditForm $edit;

    public function __construct()
    {
        $this->edit = new ArticleEditForm();
    }

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
        $this->edit->title = $article->title;
        $this->edit->summary = $article->summary;
        $this->loadArticles();
    }

    #[PageAction("cancel")]
    public function cancel(): void
    {
        $this->editingId = null;
        $this->edit = new ArticleEditForm();
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
    public function save(): void
    {
        if (!$this->validateModel($this->edit)) {
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

        $article->title = $this->edit->title;
        $article->summary = $this->edit->summary;
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
}
