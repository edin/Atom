<?php

declare(strict_types=1);

namespace Atom\Tests\Session;

use Atom\Application;
use Atom\Config\Config;
use Atom\Di\Bindings;
use Atom\Di\Injector;
use Atom\Di\ServiceProviderInterface;
use Atom\Di\ServiceProviderRegistry;
use Atom\Http\Request;
use Atom\Http\CookieJar;
use Atom\Router\Route;
use Atom\Session\ArraySession;
use Atom\Session\FlashBag;
use Atom\Session\NativeSession;
use Atom\Session\SessionInterface;
use Atom\Session\SessionOptions;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;

final class SessionTest extends TestCase
{
    protected function tearDown(): void
    {
        Application::$app = null;
        Route::clearRouter();
    }

    public function testArraySessionStoresPullsAndInvalidatesValues(): void
    {
        $session = new ArraySession(["name" => "Atom"], "initial-id");

        $this->assertFalse($session->isStarted());
        $this->assertSame("Atom", $session->get("name"));
        $this->assertTrue($session->isStarted());

        $session->put("nullable", null);
        $this->assertTrue($session->has("nullable"));
        $this->assertNull($session->get("nullable", "fallback"));
        $this->assertSame("Atom", $session->pull("name"));
        $this->assertFalse($session->has("name"));

        $session->put("user_id", 42);
        $session->invalidate();

        $this->assertSame([], $session->all());
        $this->assertNotSame("initial-id", $session->id());
    }

    public function testFlashDataLivesForTheNextSessionRequest(): void
    {
        $session = new ArraySession(id: "flash-session");
        $flash = new FlashBag($session);

        $flash->put("notice", "Saved");
        $flash->put("nullable", null);
        $this->assertSame("Saved", $flash->get("notice"));
        $this->assertNull($flash->get("nullable", "fallback"));
        $session->save();

        $next = new FlashBag($session);
        $this->assertSame("Saved", $next->get("notice"));
        $this->assertTrue($next->has("nullable"));
        $next->keep("notice");
        $this->assertNull($next->pull("nullable", "fallback"));
        $session->save();

        $kept = new FlashBag($session);
        $this->assertSame("Saved", $kept->get("notice"));
        $session->save();

        $expired = new FlashBag($session);
        $this->assertFalse($expired->has("notice"));

        $expired->now("current", "Only now");
        $session->save();
        $this->assertFalse((new FlashBag($session))->has("current"));
    }

    public function testSessionOptionsHydrateFromEnvironmentConfiguration(): void
    {
        $options = Config::fromEnv([
            "SESSION_NAME" => "BLOGSESSID",
            "SESSION_LIFETIME" => "3600",
            "SESSION_SECURE" => "true",
            "SESSION_HTTP_ONLY" => "false",
            "SESSION_SAME_SITE" => "Strict",
        ])->options(SessionOptions::class);

        $this->assertSame("BLOGSESSID", $options->name);
        $this->assertSame(3600, $options->lifetime);
        $this->assertTrue($options->secure);
        $this->assertFalse($options->httpOnly);
        $this->assertSame("Strict", $options->sameSite);
    }

    public function testApplicationClosesAResolvedSessionAfterHandlingTheRequest(): void
    {
        $app = new SessionApplication();

        $response = $app->handle(new Request("GET", "/session"));

        $this->assertSame("stored", $response->getContent());
        $this->assertFalse($app->session->isStarted());
        $this->assertSame(42, $app->session->get("user_id"));
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testNativeSessionPersistsDataUsingTheRequestCookie(): void
    {
        $options = new SessionOptions(name: "ATOMTESTSID", sameSite: "Strict");
        $firstCookies = new CookieJar();
        $first = new NativeSession($options, new Request("GET", "/"), $firstCookies);
        $first->put("user_id", 42);
        $id = $first->id();
        $this->assertCount(1, $firstCookies->all());
        $first->save();

        $secondCookies = new CookieJar();
        $second = new NativeSession($options, new Request(
            "GET",
            "/",
            headers: ["Cookie" => "theme=dark; ATOMTESTSID=" . rawurlencode($id)]
        ), $secondCookies);

        $this->assertSame($id, $second->id());
        $this->assertSame(42, $second->get("user_id"));
        $this->assertTrue($secondCookies->isEmpty());
        $second->invalidate();
        $this->assertCount(1, $secondCookies->all());
        $second->save();
    }
}

final class SessionApplication extends Application
{
    public ArraySession $session;

    public function __construct()
    {
        $this->session = new ArraySession(id: "application-session");
        parent::__construct();
    }

    protected function services(ServiceProviderRegistry $providers): void
    {
        $providers->add(new ArraySessionServices($this->session));
    }

    protected function bootstrap(Injector $injector): void
    {
        Route::get("/session", function (SessionInterface $session): string {
            $session->put("user_id", 42);
            return "stored";
        });
    }
}

final readonly class ArraySessionServices implements ServiceProviderInterface
{
    public function __construct(private ArraySession $session)
    {
    }

    public function register(Bindings $bindings): void
    {
        $bindings->bind(SessionInterface::class)
            ->toFactory(fn() => $this->session)
            ->scoped();
    }
}
