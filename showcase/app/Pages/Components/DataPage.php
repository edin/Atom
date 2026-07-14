<?php

declare(strict_types=1);

namespace Showcase\Pages\Components;

use Atom\Collections\PagedCollection;
use Atom\Hydrator\Attributes\FromBody;
use Atom\Page\PageAction;
use Atom\Page\PageRoute;
use Atom\Page\State;
use Showcase\Pages\AppPage;

#[PageRoute("/components/data", name: "showcase.components.data")]
final class DataPage extends AppPage
{
    public string $title = "Data - Atom Showcase";

    #[State]
    public int $page = 1;

    #[State]
    public string $sort = "name";

    #[State]
    public string $direction = "asc";

    #[State]
    #[FromBody]
    public string $query = "";

    #[State]
    #[FromBody]
    public string $status = "";

    /** @var list<array{value: string, text: string}> */
    public array $statuses = [
        ["value" => "", "text" => "All"],
        ["value" => "Active", "text" => "Active"],
        ["value" => "Draft", "text" => "Draft"],
    ];

    public PagedCollection $users;

    public function __construct()
    {
        $this->loadUsers();
    }

    public function get(): void
    {
        $this->loadUsers();
    }

    #[PageAction("edit")]
    public function edit(int $id): void
    {
        $this->flash("Edit action invoked for user #{$id}.", "info", "Row action");
    }

    #[PageAction("delete")]
    public function delete(int $id): void
    {
        $this->flash("Delete action invoked for user #{$id}.", "warning", "Row action");
    }

    #[PageAction("setPage")]
    public function setPage(int $page): void
    {
        $this->page = $page;
        $this->loadUsers();
    }

    #[PageAction("setSort")]
    public function setSort(string $sort, string $direction = "asc"): void
    {
        if (!in_array($sort, ["name", "role", "status"], true)) {
            return;
        }

        $this->sort = $sort;
        $this->direction = strtolower($direction) === "desc" ? "desc" : "asc";
        $this->page = 1;
        $this->loadUsers();
    }

    #[PageAction("filter")]
    public function filter(): void
    {
        $this->page = 1;
        $this->loadUsers();
    }

    #[PageAction("clearFilters")]
    public function clearFilters(): void
    {
        $this->query = "";
        $this->status = "";
        $this->page = 1;
        $this->loadUsers();
    }

    private function loadUsers(): void
    {
        $pageSize = 3;
        $users = $this->allUsers();
        $users = $this->filterUsers($users);
        $this->sortUsers($users);
        $this->page = min(max(1, $this->page), max(1, (int) ceil(count($users) / $pageSize)));

        $this->users = PagedCollection::fromPage(
            array_slice($users, ($this->page - 1) * $pageSize, $pageSize),
            totalCount: count($users),
            currentPage: $this->page,
            pageSize: $pageSize
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function allUsers(): array
    {
        return [
            ["id" => 1, "name" => "Ada Lovelace", "role" => "Admin", "status" => "Active"],
            ["id" => 2, "name" => "Grace Hopper", "role" => "Editor", "status" => "Active"],
            ["id" => 3, "name" => "Margaret Hamilton", "role" => "Author", "status" => "Draft"],
            ["id" => 4, "name" => "Katherine Johnson", "role" => "Reviewer", "status" => "Active"],
            ["id" => 5, "name" => "Radia Perlman", "role" => "Editor", "status" => "Active"],
            ["id" => 6, "name" => "Barbara Liskov", "role" => "Author", "status" => "Draft"],
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $users
     */
    private function sortUsers(array &$users): void
    {
        $field = $this->sort;
        $direction = $this->direction;

        usort($users, static function (array $left, array $right) use ($field, $direction): int {
            $result = strcasecmp((string) ($left[$field] ?? ""), (string) ($right[$field] ?? ""));

            return $direction === "desc" ? -$result : $result;
        });
    }

    /**
     * @param array<int, array<string, mixed>> $users
     * @return array<int, array<string, mixed>>
     */
    private function filterUsers(array $users): array
    {
        $query = strtolower(trim($this->query));
        $status = $this->status;

        return array_values(array_filter($users, static function (array $user) use ($query, $status): bool {
            if ($status !== "" && ($user["status"] ?? "") !== $status) {
                return false;
            }

            if ($query === "") {
                return true;
            }

            return str_contains(strtolower((string) ($user["name"] ?? "")), $query)
                || str_contains(strtolower((string) ($user["role"] ?? "")), $query);
        }));
    }
}
