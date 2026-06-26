<?php

declare(strict_types=1);

namespace Atom\Tests\Validation;

use Atom\Tests\Validation\Types\CreateArticleDto;
use Atom\Validation\Schema;
use Atom\Validation\Validation;
use Atom\Validation\Validator;
use PHPUnit\Framework\TestCase;

final class ValidationTest extends TestCase
{
    public function testFluentSchemaValidation(): void
    {
        $schema = Schema::make()
            ->field("title")->required()->maxLength(10)
            ->field("body")->required()->minLength(5)
            ->field("status")->required()->in(["draft", "published"])
            ->field("categoryId")->numeric()->min(1)
            ->schema();

        $result = $schema->validate([
            "title" => "This title is too long",
            "body" => "Body",
            "status" => "archived",
            "categoryId" => 0,
        ]);

        $this->assertTrue($result->failed());
        $this->assertTrue($result->hasErrorsFor("title"));
        $this->assertSame("max_length", $result->errorsFor("title")[0]->code);
        $this->assertSame("min_length", $result->errorsFor("body")[0]->code);
        $this->assertSame("in", $result->errorsFor("status")[0]->code);
        $this->assertSame("min", $result->errorsFor("categoryId")[0]->code);
    }

    public function testAttributeValidation(): void
    {
        $dto = new CreateArticleDto();
        $dto->title = "";
        $dto->body = "Article body";
        $dto->status = "archived";
        $dto->categoryId = "0";

        $result = Validator::for(CreateArticleDto::class)->validate($dto);

        $this->assertTrue($result->failed());
        $this->assertSame("required", $result->errorsFor("title")[0]->code);
        $this->assertSame("in", $result->errorsFor("status")[0]->code);
        $this->assertSame("min", $result->errorsFor("categoryId")[0]->code);
    }

    public function testAttributeValidationCanInferClassFromObject(): void
    {
        $dto = new CreateArticleDto();
        $dto->title = "Valid title";
        $dto->body = "Long enough body";
        $dto->status = "published";
        $dto->categoryId = 1;

        $result = Validator::for()->validate($dto);

        $this->assertTrue($result->passed());
    }

    public function testLegacyValidationBuilderStillWorksForSimpleRules(): void
    {
        $validation = Validation::create(function (Validation $rules): void {
            $rules->title->required()->maxLength(10);
            $rules->body->required();
        });

        $result = $validation->validate([
            "title" => "Too long for this field",
            "body" => "",
        ]);

        $this->assertTrue($result->failed());
        $this->assertSame("max_length", $result->errorsFor("title")[0]->code);
        $this->assertSame("required", $result->errorsFor("body")[0]->code);
    }

    public function testResultMessagesAreFormFriendly(): void
    {
        $schema = Schema::make()
            ->field("title")->required("Give this article a title.")
            ->schema();

        $result = $schema->validate(["title" => ""]);

        $this->assertSame("Give this article a title.", $result->first("title"));
        $this->assertSame([
            "title" => ["Give this article a title."],
        ], $result->messages());
    }
}

