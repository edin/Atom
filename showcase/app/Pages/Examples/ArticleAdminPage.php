<?php

declare(strict_types=1);

namespace Showcase\Pages\Examples;

use Atom\Collections\PagedCollection;
use Atom\Hydrator\Attributes\FromBody;
use Atom\Modules\Framework\Components\DialogModel;
use Atom\Modules\Framework\Components\SidePanelModel;
use Atom\Page\PageAction;
use Atom\Page\PageRoute;
use Atom\Page\State;
use Showcase\Pages\AppPage;

#[PageRoute("/examples/article-admin", name: "showcase.examples.article-admin")]
final class ArticleAdminPage extends AppPage
{
    public string $title = "Article Admin - Atom Showcase";
    public string $contentWidth = "full";

    #[State]
    public int $page = 1;

    #[State]
    public string $sort = "updated";

    #[State]
    public string $direction = "desc";

    #[State]
    #[FromBody]
    public string $query = "";

    #[State]
    #[FromBody]
    public string $status = "";

    #[State]
    public SidePanelModel $editor;

    #[State]
    public DialogModel $deleteDialog;

    #[State]
    #[FromBody]
    public int $editId = 0;

    #[State]
    #[FromBody]
    public string $editTitle = "";

    #[State]
    #[FromBody]
    public string $editSummary = "";

    #[State]
    #[FromBody]
    public string $editStatus = "Draft";

    /** @var list<array{value: string, text: string}> */
    public array $statuses = [
        ["value" => "", "text" => "All statuses"],
        ["value" => "Published", "text" => "Published"],
        ["value" => "Draft", "text" => "Draft"],
        ["value" => "Review", "text" => "Review"],
    ];

    public PagedCollection $articles;

    public function __construct()
    {
        $this->editor = new SidePanelModel();
        $this->deleteDialog = new DialogModel();
        $this->loadArticles();
    }

    public function get(): void
    {
        $this->loadArticles();
    }

    #[PageAction("create")]
    public function create(): void
    {
        $this->editId = 0;
        $this->editTitle = "";
        $this->editSummary = "";
        $this->editStatus = "Draft";
        $this->editor->open(0);
    }

    #[PageAction("edit")]
    public function edit(int $id): void
    {
        $article = $this->findArticle($id);
        if ($article === null) {
            $this->flash("Article #{$id} was not found.", "danger", "Missing article");
            return;
        }

        $this->editId = $id;
        $this->editTitle = (string) $article["title"];
        $this->editSummary = (string) $article["summary"];
        $this->editStatus = (string) $article["status"];
        $this->editor->open($id);
    }

    #[PageAction("save")]
    public function save(): void
    {
        $title = trim($this->editTitle);
        if ($title === "") {
            $this->flash("Title is required before saving.", "danger", "Validation");
            $this->editor->open($this->editId);
            return;
        }

        $this->editor->close();
        $message = $this->editId === 0
            ? "New article '{$title}' was drafted."
            : "Article '{$title}' was updated.";

        $this->flash($message, "success", "Saved");
        $this->loadArticles();
    }

    #[PageAction("askDelete")]
    public function askDelete(int $id): void
    {
        $this->deleteDialog->open($id);
    }

    #[PageAction("confirmDelete")]
    public function confirmDelete(): void
    {
        $id = (int) $this->deleteDialog->value;
        $this->deleteDialog->close();
        $this->flash("Article #{$id} would be deleted.", "warning", "Deleted");
    }

    #[PageAction("filter")]
    public function filter(): void
    {
        $this->page = 1;
        $this->loadArticles();
    }

    #[PageAction("clearFilters")]
    public function clearFilters(): void
    {
        $this->query = "";
        $this->status = "";
        $this->page = 1;
        $this->loadArticles();
    }

    #[PageAction("setPage")]
    public function setPage(int $page): void
    {
        $this->page = $page;
        $this->loadArticles();
    }

    #[PageAction("setSort")]
    public function setSort(string $sort, string $direction = "asc"): void
    {
        if (!in_array($sort, ["title", "status", "author", "updated"], true)) {
            return;
        }

        $this->sort = $sort;
        $this->direction = strtolower($direction) === "desc" ? "desc" : "asc";
        $this->page = 1;
        $this->loadArticles();
    }

    private function loadArticles(): void
    {
        $pageSize = 4;
        $articles = $this->filterArticles($this->allArticles());
        $this->sortArticles($articles);
        $this->page = min(max(1, $this->page), max(1, (int) ceil(count($articles) / $pageSize)));

        $this->articles = PagedCollection::fromPage(
            array_slice($articles, ($this->page - 1) * $pageSize, $pageSize),
            totalCount: count($articles),
            currentPage: $this->page,
            pageSize: $pageSize
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function allArticles(): array
    {
        return [
            ["id" => 101, "title" => "Building Atom components", "summary" => "How the component layer fits together.", "status" => "Published", "author" => "Edin", "updated" => "2026-07-04"],
            ["id" => 102, "title" => "Designing page actions", "summary" => "Server-driven workflows with tiny JavaScript.", "status" => "Review", "author" => "Lejla", "updated" => "2026-07-03"],
            ["id" => 103, "title" => "Database migrations", "summary" => "Schema commands, batches, and locks.", "status" => "Draft", "author" => "Amar", "updated" => "2026-06-30"],
            ["id" => 104, "title" => "API explorer workflow", "summary" => "Document and try routes from one place.", "status" => "Published", "author" => "Sara", "updated" => "2026-06-28"],
            ["id" => 105, "title" => "View state tradeoffs", "summary" => "What belongs in state and what belongs in URLs.", "status" => "Review", "author" => "Edin", "updated" => "2026-06-25"],
            ["id" => 106, "title" => "Hydration attributes", "summary" => "Binding request input into DTOs and pages.", "status" => "Draft", "author" => "Lejla", "updated" => "2026-06-23"],
            ["id" => 107, "title" => "Console commands", "summary" => "Discovery, parameters, and framework commands.", "status" => "Published", "author" => "Amar", "updated" => "2026-06-20"],
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $articles
     * @return array<int, array<string, mixed>>
     */
    private function filterArticles(array $articles): array
    {
        $query = strtolower(trim($this->query));
        $status = $this->status;

        return array_values(array_filter($articles, static function (array $article) use ($query, $status): bool {
            if ($status !== "" && ($article["status"] ?? "") !== $status) {
                return false;
            }

            if ($query === "") {
                return true;
            }

            return str_contains(strtolower((string) ($article["title"] ?? "")), $query)
                || str_contains(strtolower((string) ($article["summary"] ?? "")), $query)
                || str_contains(strtolower((string) ($article["author"] ?? "")), $query);
        }));
    }

    /**
     * @param array<int, array<string, mixed>> $articles
     */
    private function sortArticles(array &$articles): void
    {
        $field = $this->sort;
        $direction = $this->direction;

        usort($articles, static function (array $left, array $right) use ($field, $direction): int {
            $result = strcasecmp((string) ($left[$field] ?? ""), (string) ($right[$field] ?? ""));

            return $direction === "desc" ? -$result : $result;
        });
    }

    /**
     * @return array<string, mixed>|null
     */
    private function findArticle(int $id): ?array
    {
        foreach ($this->allArticles() as $article) {
            if ((int) $article["id"] === $id) {
                return $article;
            }
        }

        return null;
    }
}
