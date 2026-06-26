<?php

declare(strict_types=1);

namespace Atom\Tests\Hydrator;

use Atom\Http\Request;
use Atom\Http\UploadedFile;
use Atom\Hydrator\Attributes\DateFormat;
use Atom\Hydrator\Attributes\Dto;
use Atom\Hydrator\Attributes\FromBody;
use Atom\Hydrator\Attributes\FromFile;
use Atom\Hydrator\Attributes\FromHeader;
use Atom\Hydrator\Attributes\FromQuery;
use Atom\Hydrator\Attributes\FromRoute;
use Atom\Hydrator\Attributes\Raw;
use Atom\Hydrator\Exception\HydrationException;
use Atom\Hydrator\HydrationContext;
use Atom\Hydrator\HydrationTarget;
use Atom\Hydrator\RequestHydrator;
use PHPUnit\Framework\TestCase;

final class RequestHydratorTest extends TestCase
{
    public function testHydratesDtoPropertiesWithImplicitCoercion(): void
    {
        $hydrator = new RequestHydrator();
        $context = new HydrationContext(
            body: [
                "name" => "  Atom  ",
                "age" => "42",
                "enabled" => "yes",
                "score" => "10.5",
            ],
            query: ["invite" => ""]
        );

        $dto = $hydrator->hydrate(CreateUserDto::class, $context);

        $this->assertInstanceOf(CreateUserDto::class, $dto);
        $this->assertSame("Atom", $dto->name);
        $this->assertSame(42, $dto->age);
        $this->assertTrue($dto->enabled);
        $this->assertSame(10.5, $dto->score);
        $this->assertNull($dto->inviteCode);
    }

    public function testHydratesFromRouteHeaderAndFileSources(): void
    {
        $request = new Request(
            "POST",
            "/users/10",
            headers: ["X-Trace" => "abc"],
            files: [
                "avatar" => [
                    "name" => "avatar.png",
                    "tmp_name" => "/tmp/avatar",
                    "size" => 123,
                    "error" => UPLOAD_ERR_OK,
                    "type" => "image/png",
                ],
            ]
        );
        $context = HydrationContext::fromRequest($request, ["id" => "10"]);

        $dto = (new RequestHydrator())->hydrate(SourceDto::class, $context);

        $this->assertSame(10, $dto->id);
        $this->assertSame("abc", $dto->trace);
        $this->assertInstanceOf(UploadedFile::class, $dto->avatar);
        $this->assertSame("avatar.png", $dto->avatar->name);
    }

    public function testRawStringIsNotTrimmed(): void
    {
        $dto = (new RequestHydrator())->hydrate(RawDto::class, new HydrationContext(
            body: ["password" => "  secret  "]
        ));

        $this->assertSame("  secret  ", $dto->password);
    }

    public function testDateFormatTransformsBeforeDateCoercion(): void
    {
        $dto = (new RequestHydrator())->hydrate(DateDto::class, new HydrationContext(
            body: ["birthDate" => "24.06.2026"]
        ));

        $this->assertInstanceOf(\DateTimeImmutable::class, $dto->birthDate);
        $this->assertSame("2026-06-24", $dto->birthDate->format("Y-m-d"));
    }

    public function testRequiredMissingPropertyThrowsHydrationException(): void
    {
        $this->expectException(HydrationException::class);
        $this->expectExceptionMessage(CreateUserDto::class . "::name");

        (new RequestHydrator())->hydrate(CreateUserDto::class, new HydrationContext());
    }

    public function testInvalidScalarThrowsHydrationException(): void
    {
        $this->expectException(HydrationException::class);
        $this->expectExceptionMessage("expected int");

        (new RequestHydrator())->hydrate(CreateUserDto::class, new HydrationContext(
            body: ["name" => "Atom", "age" => "not-int"]
        ));
    }

    public function testHydrationTargetCanRepresentParameters(): void
    {
        $method = new \ReflectionMethod(ParameterTargetExample::class, "handle");
        $target = HydrationTarget::fromParameter($method->getParameters()[0]);

        $this->assertSame("id", $target->name);
        $this->assertSame("int", $target->typeName);
        $this->assertTrue($target->isBuiltin);
        $this->assertFalse($target->allowsNull);
        $this->assertSame("route", $target->source);
        $this->assertSame("userId", $target->sourceName);
    }
}

#[Dto]
final class CreateUserDto
{
    #[FromBody]
    public string $name;

    #[FromBody]
    public int $age;

    #[FromBody]
    public bool $enabled = false;

    #[FromBody]
    public float $score = 0;

    #[FromQuery("invite")]
    public ?string $inviteCode = null;
}

#[Dto]
final class SourceDto
{
    #[FromRoute]
    public int $id;

    #[FromHeader("X-Trace")]
    public string $trace;

    #[FromFile]
    public UploadedFile $avatar;
}

#[Dto]
final class RawDto
{
    #[FromBody]
    #[Raw]
    public string $password;
}

#[Dto]
final class DateDto
{
    #[FromBody]
    #[DateFormat("d.m.Y")]
    public \DateTimeImmutable $birthDate;
}

final class ParameterTargetExample
{
    public function handle(#[FromRoute("userId")] int $id): void
    {
    }
}
