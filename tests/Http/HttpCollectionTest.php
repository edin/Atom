<?php

declare(strict_types=1);

namespace Atom\Tests\Http;

use Atom\Http\HeaderCollection;
use Atom\Http\ContentStream;
use Atom\Http\CookieCollection;
use Atom\Http\Cookie;
use Atom\Http\CookieJar;
use Atom\Http\ParameterCollection;
use Atom\Http\Request;
use Atom\Http\Response;
use Atom\Http\StreamedContent;
use Atom\Http\UploadedFile;
use PHPUnit\Framework\TestCase;

final class HttpCollectionTest extends TestCase
{
    public function testParametersCanBeReadAsTypedValues(): void
    {
        $parameters = new ParameterCollection([
            "name" => "Atom",
            "page" => "2",
            "enabled" => "true",
            "tags" => ["php"],
        ]);

        $this->assertSame("Atom", $parameters->string("name"));
        $this->assertSame(2, $parameters->int("page"));
        $this->assertTrue($parameters->bool("enabled"));
        $this->assertSame("fallback", $parameters->string("tags", "fallback"));
        $this->assertSame(["name" => "Atom"], $parameters->only(["name"]));
        $this->assertSame(["page" => "2", "enabled" => "true", "tags" => ["php"]], $parameters->except(["name"]));
    }

    public function testHeadersAreCaseInsensitiveAndKeepMultipleValues(): void
    {
        $headers = new HeaderCollection(["Content-Type" => "text/plain"]);

        $headers->add("content-type", "charset=utf-8");
        $headers->set("X-Request-Id", "abc");

        $this->assertTrue($headers->has("CONTENT-TYPE"));
        $this->assertSame("text/plain, charset=utf-8", $headers->get("Content-Type"));
        $this->assertSame(["text/plain", "charset=utf-8"], $headers->all("content-type"));
        $this->assertSame([
            "Content-Type" => ["text/plain", "charset=utf-8"],
            "X-Request-Id" => ["abc"],
        ], $headers->toArray());
    }

    public function testCookiesAreParsedWithoutPhpNameOrValueMangling(): void
    {
        $cookies = CookieCollection::fromHeader(
            'theme=dark; preferences.theme=compact; token=a+b%2Bc; quoted="hello%20world"; payload=a=b; theme=light; invalid'
        );

        $this->assertSame("dark", $cookies->string("theme"));
        $this->assertSame("compact", $cookies->string("preferences.theme"));
        $this->assertSame("a+b+c", $cookies->string("token"));
        $this->assertSame("hello world", $cookies->string("quoted"));
        $this->assertSame("a=b", $cookies->string("payload"));
        $this->assertFalse($cookies->has("invalid"));
        $this->assertSame(["theme" => "dark"], $cookies->only(["theme"]));
        $this->assertSame("compact", $cookies["preferences.theme"]);
    }

    public function testRequestCookiesAreReadOnly(): void
    {
        $cookies = CookieCollection::fromHeader("theme=dark");

        $this->expectException(\LogicException::class);
        $cookies["theme"] = "light";
    }

    public function testRequestExposesHttpCollectionsAndArrayCompatibility(): void
    {
        $request = new Request(
            "post",
            "/submit",
            ["page" => "3"],
            ["name" => "Atom"],
            "{\"json\":true}",
            ["HTTP_ACCEPT" => "application/json"],
            [
                "avatar" => [
                    "name" => "avatar.png",
                    "tmp_name" => "/tmp/avatar",
                    "size" => 123,
                    "error" => UPLOAD_ERR_OK,
                    "type" => "image/png",
                ],
            ]
        );

        $avatar = $request->files()->file("avatar");

        $this->assertSame("POST", $request->getMethod());
        $this->assertSame("3", $request->query()->string("page"));
        $this->assertSame("Atom", $request->post()->string("name"));
        $this->assertInstanceOf(UploadedFile::class, $avatar);
        $this->assertSame("avatar.png", $avatar->name);
        $this->assertTrue($avatar->isValid());
        $this->assertSame("application/json", $request->headers()->get("Accept"));
        $this->assertSame(["page" => "3"], $request->getQueryParams());
        $this->assertSame(["name" => "Atom"], $request->getParsedBody());
    }

    public function testRequestExposesCookiesParsedFromServerHeaders(): void
    {
        $request = new Request(
            "GET",
            "/",
            serverParams: ["HTTP_COOKIE" => "theme=dark; locale=en%2DGB"]
        );

        $this->assertSame("dark", $request->cookies()->string("theme"));
        $this->assertSame("en-GB", $request->cookies()->string("locale"));
        $this->assertSame("fallback", $request->cookies()->string("missing", "fallback"));
    }

    public function testRequestDetectsDirectHttpsConnections(): void
    {
        $this->assertTrue((new Request("GET", "/", serverParams: ["HTTPS" => "on"]))->isSecure());
        $this->assertTrue((new Request("GET", "/", serverParams: ["SERVER_PORT" => 443]))->isSecure());
        $this->assertFalse((new Request("GET", "/", serverParams: ["SERVER_PORT" => 80]))->isSecure());
    }

    public function testRequestCanCreateCopyWithDifferentMethod(): void
    {
        $request = new Request(
            "POST",
            "/articles",
            ["page" => "2"],
            ["_state" => "abc"],
            serverParams: [
                "HTTP_X_ATOM_INTENT" => "navigate",
                "HTTP_COOKIE" => "theme=dark",
            ]
        );

        $copy = $request->withMethod("GET");

        $this->assertSame("POST", $request->getMethod());
        $this->assertSame("GET", $copy->getMethod());
        $this->assertSame("/articles", $copy->getPath());
        $this->assertSame("2", $copy->query()->string("page"));
        $this->assertSame("abc", $copy->post()->string("_state"));
        $this->assertSame("navigate", $copy->headers()->get("X-Atom-Intent"));
        $this->assertSame("dark", $copy->cookies()->string("theme"));
    }

    public function testFileCollectionSupportsMultipleUploads(): void
    {
        $request = new Request(
            "POST",
            "/upload",
            files: [
                "photos" => [
                    "name" => ["one.png", "two.png"],
                    "tmp_name" => ["/tmp/one", "/tmp/two"],
                    "size" => [10, 20],
                    "error" => [UPLOAD_ERR_OK, UPLOAD_ERR_OK],
                    "type" => ["image/png", "image/png"],
                ],
            ]
        );

        $photos = $request->files()->files("photos");

        $this->assertCount(2, $photos);
        $this->assertSame("one.png", $photos[0]->name);
        $this->assertSame(20, $photos[1]->size);
    }

    public function testResponseUsesHeaderCollection(): void
    {
        $response = new Response();

        $response
            ->header("Content-Type", "application/json")
            ->addHeader("Set-Cookie", "a=1")
            ->addHeader("Set-Cookie", "b=2");

        $this->assertSame("application/json", $response->headers()->get("content-type"));
        $this->assertSame(["a=1", "b=2"], $response->headers()->all("set-cookie"));
        $this->assertSame([
            "Content-Type" => ["application/json"],
            "Set-Cookie" => ["a=1", "b=2"],
        ], $response->getHeaders());
    }

    public function testCookieBuildsSafeSetCookieHeader(): void
    {
        $cookie = Cookie::create("preferences.theme", "dark mode")
            ->expiresAt(new \DateTimeImmutable("2030-01-02 03:04:05 UTC"))
            ->withMaxAge(3600)
            ->withPath("/admin")
            ->withDomain("example.test")
            ->withSameSite("strict")
            ->withSecure();

        $this->assertSame(
            "preferences.theme=dark%20mode; Expires=Wed, 02 Jan 2030 03:04:05 GMT; Max-Age=3600; " .
            "Path=/admin; Domain=example.test; Secure; HttpOnly; SameSite=Strict",
            $cookie->toHeader()
        );
    }

    public function testSameSiteNoneCookieMustBeSecureWhenSerialized(): void
    {
        $cookie = Cookie::create("cross_site", "allowed")->withSameSite("None");

        $this->expectException(\InvalidArgumentException::class);
        $cookie->toHeader();
    }

    public function testResponseCanSetAndRemoveCookiesDirectly(): void
    {
        $response = new Response();

        $response
            ->cookie(Cookie::create("theme", "dark"))
            ->removeCookie("legacy");

        $headers = $response->headers()->all("Set-Cookie");
        $this->assertSame("theme=dark; Path=/; HttpOnly; SameSite=Lax", $headers[0]);
        $this->assertSame(
            "legacy=; Expires=Thu, 01 Jan 1970 00:00:00 GMT; Max-Age=0; Path=/; HttpOnly; SameSite=Lax",
            $headers[1]
        );
    }

    public function testCookieJarAppliesQueuedCookieToADifferentFinalResponse(): void
    {
        $jar = new CookieJar();
        $otherResponse = new Response($jar);
        $otherResponse->cookie(Cookie::create("theme", "dark"));

        $finalResponse = new Response();
        $jar->apply($finalResponse);

        $this->assertSame([], $otherResponse->headers()->all("Set-Cookie"));
        $this->assertSame(
            ["theme=dark; Path=/; HttpOnly; SameSite=Lax"],
            $finalResponse->headers()->all("Set-Cookie")
        );
        $this->assertTrue($jar->isEmpty());
    }

    public function testContentStreamBuildsResponseContent(): void
    {
        $stream = new ContentStream();

        $stream
            ->write("Hello")
            ->write(" ")
            ->line("Atom")
            ->write("Framework");

        $this->assertSame("Hello Atom" . PHP_EOL . "Framework", $stream->getContents());
        $this->assertSame(strlen($stream->getContents()), $stream->length());

        $stream->replace("Done");

        $this->assertSame("Done", (string) $stream);
        $this->assertFalse($stream->isEmpty());

        $stream->clear();

        $this->assertTrue($stream->isEmpty());
    }

    public function testResponseBodyStreamCanBeUsedDirectly(): void
    {
        $response = new Response();

        $response->body()
            ->write("Hello")
            ->line(" Atom");

        $response->write("Framework");

        $this->assertSame("Hello Atom" . PHP_EOL . "Framework", $response->getContent());

        $response->content("Replaced");

        $this->assertSame("Replaced", $response->body()->getContents());
    }

    public function testResponseCanStreamContentWithoutBuffering(): void
    {
        $response = new Response();

        $response
            ->header("Content-Type", "application/xml")
            ->stream(function (callable $write): void {
                $write("<?xml version=\"1.0\"?>");
                $write("<items>");
                $write("<item id=\"1\" />");
                $write("</items>");
            });

        $this->assertInstanceOf(StreamedContent::class, $response->getBody());
        $this->assertSame("", $response->getContent());

        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        $this->assertSame("<?xml version=\"1.0\"?><items><item id=\"1\" /></items>", $content);
    }

    public function testWritingAfterStreamingSwitchesBackToBufferedContent(): void
    {
        $response = new Response();

        $response->stream(function (callable $write): void {
            $write("streamed");
        });

        $response->content("buffered");

        $this->assertInstanceOf(ContentStream::class, $response->getBody());
        $this->assertSame("buffered", $response->getContent());
    }
}
