<?php

declare(strict_types=1);

namespace Atom\Tests\Logging;

use Atom\Application;
use Atom\Di\Injector;
use Atom\Logging\FileLogger;
use Atom\Logging\Log;
use Atom\Logging\Logger;
use Atom\Modules\Logging\Logging;
use Atom\Modules\Logging\LoggingOptions;
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

        $logger = $app->getInjector()->get(Logger::class);

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

        $app->getInjector()->get(Logger::class)->info("Configured");

        $contents = file_get_contents($path);

        $this->assertIsString($contents);
        $this->assertStringContainsString("INFO: Configured", $contents);
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
