<?php

declare(strict_types=1);

namespace Atom\Tests\Dispatcher;

use Atom\Di\Bindings;
use Atom\Di\InjectionContext;
use Atom\Di\Injector;
use Atom\Dispatcher\MiddlewarePipeline;
use Atom\Dispatcher\ResponseResultInterface;
use Atom\Dispatcher\ResultConverter;
use Atom\Dispatcher\ResultHandlerRegistry;
use Atom\Http\MiddlewareInterface;
use Atom\Http\Request;
use Atom\Http\RequestHandlerInterface;
use Atom\Http\Response;
use Atom\Interfaces\IResponsable;
use PHPUnit\Framework\TestCase;

final class DispatcherTest extends TestCase
{
    public function testMiddlewarePipelineCanBeReused(): void
    {
        $middleware = new CountingMiddleware();
        $pipeline = new MiddlewarePipeline([$middleware], new TextHandler("done"));

        $first = $pipeline->handle(new Request("GET", "/"));
        $second = $pipeline->handle(new Request("GET", "/"));

        $this->assertSame("done", $first->getContent());
        $this->assertSame("done", $second->getContent());
        $this->assertSame(2, $middleware->count);
    }

    public function testResultConverterReturnsResponseResultDirectly(): void
    {
        $response = new Response();
        $converter = $this->converter($response);

        $this->assertSame($response, $converter->toResponse($response));
    }

    public function testResultConverterUsesResponsableResult(): void
    {
        $response = new Response();
        $converter = $this->converter($response);

        $result = $converter->toResponse(new ResponsableResult());

        $this->assertSame("responsable", $result->getContent());
        $this->assertSame("yes", $result->headers()->get("X-Responsable"));
    }

    public function testResultConverterUsesResponseResultInterface(): void
    {
        $response = new Response();
        $converter = $this->converter($response);

        $result = $converter->toResponse(new ResponseResult());

        $this->assertSame("response-result", $result->getContent());
    }

    public function testResultConverterUsesCommonResultHandlers(): void
    {
        $response = new Response();
        $converter = $this->converter($response);

        $result = $converter->toResponse(["name" => "Atom"]);

        $this->assertSame('{"name":"Atom"}', $result->getContent());
        $this->assertSame("application/json", $result->headers()->get("Content-Type"));
    }

    public function testResultConverterConvertsJsonSerializableAndStdClassToJson(): void
    {
        $jsonSerializable = $this->converter(new Response())->toResponse(new JsonSerializableResult());
        $object = new \stdClass();
        $object->name = "Atom";
        $stdClass = $this->converter(new Response())->toResponse($object);

        $this->assertSame('{"name":"Atom"}', $jsonSerializable->getContent());
        $this->assertSame('{"name":"Atom"}', $stdClass->getContent());
    }

    public function testResultConverterDoesNotJsonEncodeArbitraryObjects(): void
    {
        $result = $this->converter(new Response())->toResponse(new PlainObjectResult());

        $this->assertSame("plain-object", $result->getContent());
    }

    private function converter(Response $response): ResultConverter
    {
        $bindings = Bindings::create();
        $bindings->value(Response::class, $response);

        $injector = new Injector($bindings);
        return new ResultConverter($injector, $response, new ResultHandlerRegistry($injector));
    }
}

final class CountingMiddleware implements MiddlewareInterface
{
    public int $count = 0;

    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        $this->count++;
        return $handler->handle($request);
    }
}

final readonly class TextHandler implements RequestHandlerInterface
{
    public function __construct(private string $text)
    {
    }

    public function handle(Request $request): Response
    {
        return (new Response())->content($this->text);
    }
}

final readonly class ResponsableResult implements IResponsable
{
    public function toResponse($context): Response
    {
        return (new Response())
            ->header("X-Responsable", "yes")
            ->content("responsable");
    }
}

final readonly class ResponseResult implements ResponseResultInterface
{
    public function toResponse(Injector $injector, InjectionContext $context): Response
    {
        return (new Response())->content("response-result");
    }
}

final readonly class JsonSerializableResult implements \JsonSerializable
{
    public function jsonSerialize(): mixed
    {
        return ["name" => "Atom"];
    }
}

final readonly class PlainObjectResult
{
    public function __toString(): string
    {
        return "plain-object";
    }
}
