<?php

declare(strict_types=1);

namespace Atom\Tests\Logging;

use Atom\Application;
use Atom\Di\Injector;
use Atom\Logging\FileLogger;
use Atom\Logging\Log;
use Atom\Logging\LoggerInterface;
use Atom\Modules\Logging\Logging;
use Atom\Modules\Logging\LoggingOptions;
use Atom\Module\ModuleRegistry;
use Atom\Http\Request;
use Atom\Router\Route;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class LoggingTest extends TestCase
{
    protected function tearDown(): void
    {
        Application::$app = null;
        Route::clearRouter();
    }

    public function testFileLoggerWritesInfoAndErrorLines(): void
    {
        $path = $this->tempPath();
        $logger = new FileLogger($path);

        $logger->info("Started", ["id" => 42]);
        $logger->error("Failed", ["exception" => new RuntimeException("Boom")]);

        $contents = file_get_contents($path);

        $this->assertIsString($contents);
        $this->assertStringContainsString("INFO: Started", $contents);
        $this->assertStringContainsString('"id":42', $contents);
        $this->assertStringContainsString("ERROR: Failed", $contents);
        $this->assertStringContainsString('"message":"Boom"', $contents);
    }

    public function testLogFacadeFallsBackToNullLogger(): void
    {
        Log::info("No app yet");

        $this->assertTrue(true);
    }

    public function testLoggingModuleRegistersFileLogger(): void
    {
        $path = $this->tempPath();
        $app = new LoggingTestApplication();
        $app->initialize();
        $app->registerModule(Logging::file($path));

        $logger = $app->getInjector()->get(LoggerInterface::class);

        $this->assertInstanceOf(FileLogger::class, $logger);

        $logger->info("Injected");
        Log::error("Facade");

        $contents = file_get_contents($path);

        $this->assertIsString($contents);
        $this->assertStringContainsString("INFO: Injected", $contents);
        $this->assertStringContainsString("ERROR: Facade", $contents);
    }

    public function testLoggingModuleReadsOptionsFromConfig(): void
    {
        $path = $this->tempPath();
        $app = new LoggingTestApplication();
        $app->initialize();
        $app->getConfig()->set(new LoggingOptions($path));

        $app->registerModule(Logging::module());

        $app->getInjector()->get(LoggerInterface::class)->info("Configured");

        $contents = file_get_contents($path);

        $this->assertIsString($contents);
        $this->assertStringContainsString("INFO: Configured", $contents);
    }

    public function testUnhandledExceptionLogsRequestAndRouteContextThroughModule(): void
    {
        $path = $this->tempPath();
        $app = new ExceptionLoggingTestApplication($path);

        $response = $app->handle(new Request("GET", "/explode", serverParams: [
            "REMOTE_ADDR" => "198.51.100.9",
        ], headers: ["X-Request-Id" => "request_456"]));
        $contents = file_get_contents($path);

        $this->assertSame(500, $response->getStatus());
        $this->assertIsString($contents);
        $this->assertStringContainsString("ERROR: Unhandled exception", $contents);
        $this->assertStringContainsString('"request_id":"request_456"', $contents);
        $this->assertStringContainsString('"client_ip":"198.51.100.9"', $contents);
        $this->assertStringContainsString('"route_name":"explode"', $contents);
        $this->assertStringContainsString('"message":"route failed"', $contents);
    }

    private function tempPath(): string
    {
        return sys_get_temp_dir() . DIRECTORY_SEPARATOR . "atom_log_" . uniqid() . DIRECTORY_SEPARATOR . "app.log";
    }
}

final class LoggingTestApplication extends Application
{
    protected function bootstrap(Injector $injector): void
    {
    }
}

final class ExceptionLoggingTestApplication extends Application
{
    public function __construct(private readonly string $logPath)
    {
        parent::__construct();
    }

    protected function modules(ModuleRegistry $modules): void
    {
        $modules->add(Logging::file($this->logPath));
    }

    protected function bootstrap(Injector $injector): void
    {
        Route::get("/explode", static function (): never {
            throw new RuntimeException("route failed");
        })->name("explode");
    }
}
