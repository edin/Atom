<?php

declare(strict_types=1);

namespace Atom\Tests\Dispatcher;

use Atom\Di\Bindings;
use Atom\Di\InjectionContext;
use Atom\Di\Injector;
use Atom\Dispatcher\MiddlewarePipeline;
use Atom\Dispatcher\Dispatcher;
use Atom\Dispatcher\DispatcherServices;
use Atom\Dispatcher\ResponseResultInterface;
use Atom\Dispatcher\ResultConverter;
use Atom\Dispatcher\ResultHandlerRegistry;
use Atom\Http\MiddlewareInterface;
use Atom\Http\Request;
use Atom\Http\RequestHandlerInterface;
use Atom\Http\Response;
use Atom\Interfaces\IResponsable;
use Atom\Router\RouteAction;
use Atom\Router\RouteEntry;
use Atom\Router\Router;
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

    public function testDispatcherReturnsNotFoundResponseForMissingRoute(): void
    {
        $dispatcher = $this->dispatcher(new Router());

        $response = $dispatcher->handle(new Request("GET", "/favicon.ico"));

        $this->assertSame(404, $response->getStatus());
        $this->assertSame("Not Found", $response->getContent());
    }

    public function testDispatcherReturnsMethodNotAllowedResponse(): void
    {
        $router = new Router();
        $router->add(RouteEntry::route("POST", "/articles", RouteAction::fromClosure(fn(): string => "created")));
        $dispatcher = $this->dispatcher($router);

        $response = $dispatcher->handle(new Request("GET", "/articles"));

        $this->assertSame(405, $response->getStatus());
        $this->assertSame("POST", $response->headers()->get("Allow"));
        $this->assertSame("Method Not Allowed", $response->getContent());
    }

    private function converter(Response $response): ResultConverter
    {
        $bindings = Bindings::create();
        $bindings->value(Response::class, $response);

        $injector = new Injector($bindings);
        return new ResultConverter($injector, $response, new ResultHandlerRegistry($injector));
    }

    private function dispatcher(Router $router): Dispatcher
    {
        $bindings = Bindings::create();
        (new DispatcherServices())->register($bindings);
        $bindings->value(Router::class, $router);

        return Injector::create($bindings)->get(Dispatcher::class);
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
