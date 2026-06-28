<?php

declare(strict_types=1);

namespace Atom\Tests\ApiExplorer;

use Atom\ApiExplorer\Attributes\ArrayOf;
use Atom\ApiExplorer\Attributes\ErrorResponse;
use Atom\ApiExplorer\Attributes\ResponseGeneric;
use Atom\ApiExplorer\ApiModelBuilder;
use Atom\Hydrator\Attributes\Dto;
use Atom\Hydrator\Attributes\FromBody;
use Atom\Hydrator\Attributes\FromQuery;
use Atom\Router\RouteEntry;
use Atom\Router\RouteAction;
use Atom\Router\Router;
use Atom\Validation\Rules\MaxLength;
use Atom\Validation\Rules\Required;
use PHPUnit\Framework\TestCase;

final class ApiModelBuilderTest extends TestCase
{
    public function testDescribesControllerRouteAndDtoFields(): void
    {
        $router = new Router();
        $router->add(
            RouteEntry::route("POST", "/api/users/{id}", RouteAction::fromMethod(ApiUsersController::class, "update"))
                ->name("users.update")
                ->title("Update user")
                ->description("Updates one user.")
        );
        $router->add(
            RouteEntry::route("GET", "/api/articles", RouteAction::fromMethod(ApiUsersController::class, "articles"))
                ->name("articles.index")
        );

        $description = (new ApiModelBuilder())->describe($router);

        $endpoint = $description->endpoints[0];
        $this->assertSame(["POST"], $endpoint->methods);
        $this->assertSame("/api/users/{id}", $endpoint->path);
        $this->assertSame("users.update", $endpoint->name);
        $this->assertSame(ApiUsersController::class . "::update", $endpoint->handler);
        $this->assertSame(ApiUserResponse::class, $endpoint->responseType);
        $this->assertCount(3, $endpoint->responseFields);
        $this->assertSame("id", $endpoint->responseFields[0]->name);
        $this->assertSame("response", $endpoint->responseFields[0]->source);
        $this->assertSame("int", $endpoint->responseFields[0]->type);
        $this->assertTrue($endpoint->responseFields[0]->required);
        $this->assertSame("displayName", $endpoint->responseFields[1]->name);
        $this->assertSame("?string", $endpoint->responseFields[1]->type);
        $this->assertFalse($endpoint->responseFields[1]->required);

        $this->assertCount(4, $endpoint->requestFields);
        $this->assertSame("id", $endpoint->requestFields[0]->name);
        $this->assertSame("route", $endpoint->requestFields[0]->source);
        $this->assertSame("int", $endpoint->requestFields[0]->type);

        $this->assertSame("name", $endpoint->requestFields[1]->name);
        $this->assertSame("body", $endpoint->requestFields[1]->source);
        $this->assertTrue($endpoint->requestFields[1]->required);
        $this->assertSame([Required::class, MaxLength::class], $endpoint->requestFields[1]->validationRules);

        $this->assertSame("inviteCode", $endpoint->requestFields[3]->name);
        $this->assertSame("query", $endpoint->requestFields[3]->source);
        $this->assertSame("invite", $endpoint->requestFields[3]->sourceName);
        $this->assertSame("?string", $endpoint->requestFields[3]->type);
        $this->assertFalse($endpoint->requestFields[3]->required);

        $articlesEndpoint = $description->endpoints[1];
        $this->assertSame(ApiPageResponse::class, $articlesEndpoint->responseType);
        $this->assertCount(3, $articlesEndpoint->responseFields);
        $this->assertSame("items", $articlesEndpoint->responseFields[0]->name);
        $this->assertSame("ApiArticleResponse[]", $articlesEndpoint->responseFields[0]->type);
        $this->assertCount(3, $articlesEndpoint->responseFields[0]->children);
        $this->assertSame("author", $articlesEndpoint->responseFields[0]->children[2]->name);
        $this->assertSame(ApiAuthorResponse::class, $articlesEndpoint->responseFields[0]->children[2]->type);
        $this->assertCount(3, $articlesEndpoint->responseFields[0]->children[2]->children);
        $this->assertSame("name", $articlesEndpoint->responseFields[0]->children[2]->children[1]->name);
        $this->assertSame("profile", $articlesEndpoint->responseFields[0]->children[2]->children[2]->name);
        $this->assertSame([], $articlesEndpoint->responseFields[0]->children[2]->children[2]->children);
        $this->assertCount(1, $articlesEndpoint->errorResponses);
        $this->assertSame(404, $articlesEndpoint->errorResponses[0]->status);
        $this->assertSame(ApiNotFoundResponse::class, $articlesEndpoint->errorResponses[0]->type);
        $this->assertSame("Article was not found.", $articlesEndpoint->errorResponses[0]->description);
        $this->assertSame("message", $articlesEndpoint->errorResponses[0]->fields[0]->name);
    }
}

final class ApiUsersController
{
    public function update(int $id, ApiCreateUserDto $dto): ApiUserResponse
    {
        return new ApiUserResponse();
    }

    #[ResponseGeneric("T", ApiArticleResponse::class)]
    #[ErrorResponse(404, ApiNotFoundResponse::class, "Article was not found.")]
    public function articles(): ApiPageResponse
    {
        return new ApiPageResponse();
    }
}

#[Dto]
final class ApiCreateUserDto
{
    #[FromBody]
    #[Required]
    #[MaxLength(80)]
    public string $name;

    #[FromBody]
    public int $age;

    #[FromQuery("invite")]
    public ?string $inviteCode = null;
}

final class ApiUserResponse
{
    public int $id;
    public ?string $displayName = null;
    public bool $active;
}

final class ApiPageResponse
{
    #[ArrayOf("T")]
    public array $items = [];

    public int $total;
    public int $page;
}

final class ApiArticleResponse
{
    public int $id;
    public string $title;
    public ApiAuthorResponse $author;
}

final class ApiAuthorResponse
{
    public int $id;
    public string $name;
    public ApiAuthorProfileResponse $profile;
}

final class ApiAuthorProfileResponse
{
    public ApiAuthorWebsiteResponse $website;
}

final class ApiAuthorWebsiteResponse
{
    public string $url;
}

final class ApiNotFoundResponse
{
    public string $message;
}
