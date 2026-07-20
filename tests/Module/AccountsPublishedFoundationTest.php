<?php

declare(strict_types=1);

namespace Atom\Tests\Module;

use App\Accounts\AccountManager;
use App\Identity\AppIdentityProvider;
use App\Models\PasswordResetToken;
use App\Models\User;
use Atom\Application;
use Atom\Database\DatabaseConnection;
use Atom\Database\Db;
use Atom\Database\Driver\SqliteDriver;
use Atom\Database\Model;
use Atom\Identity\NativePasswordHasher;
use Atom\Modules\Accounts\AccountsPublishBundle;
use Atom\Modules\Accounts\AccountsRoutes;
use Atom\Modules\Accounts\Jobs\SendPasswordResetJob;
use Atom\Modules\Accounts\RegisterAccount;
use Atom\Publish\Publisher;
use Atom\Queue\JobDispatcherInterface;
use Atom\Queue\JobInterface;
use Atom\Support\Paths;
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . "/src/Modules/Accounts/Publish/Models/User.php";
require_once dirname(__DIR__, 2) . "/src/Modules/Accounts/Publish/Models/PasswordResetToken.php";
require_once dirname(__DIR__, 2) . "/src/Modules/Accounts/Publish/Identity/AppIdentityProvider.php";
require_once dirname(__DIR__, 2) . "/src/Modules/Accounts/Publish/Accounts/AccountManager.php";

final class AccountsPublishedFoundationTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "atom_accounts_publish_" . uniqid();
        mkdir($this->root, 0777, true);
    }

    protected function tearDown(): void
    {
        Application::$app = null;
        $this->removeDirectory($this->root);
    }

    public function testBundlePublishesCustomizableApplicationFoundationWithoutOverwriting(): void
    {
        $paths = (new Paths($this->root))
            ->alias("app", $this->root . "/app")
            ->alias("migrations", $this->root . "/app/Database/Migrations");
        $publisher = new Publisher($paths);
        $bundle = (new AccountsPublishBundle())->bundle();

        $published = $publisher->publish($bundle);
        $skipped = $publisher->publish($bundle);

        $this->assertCount(7, $published->published);
        $this->assertCount(7, $skipped->skipped);
        $this->assertFileExists($this->root . "/app/Models/User.php");
        $this->assertFileExists($this->root . "/app/Models/PasswordResetToken.php");
        $this->assertFileExists($this->root . "/app/Identity/AppIdentityProvider.php");
        $this->assertFileExists($this->root . "/app/Accounts/AccountManager.php");
        $this->assertFileExists($this->root . "/app/Providers/AccountsServiceProvider.php");
        $this->assertFileExists($this->root . "/app/Database/Migrations/M0001_create_users.php");
        $this->assertFileExists(
            $this->root . "/app/Database/Migrations/M0002_create_password_reset_tokens.php"
        );
    }

    public function testPublishedManagerRegistersAuthenticatesAndResetsPasswordWithSingleUseToken(): void
    {
        $db = new Db(new DatabaseConnection(SqliteDriver::memory()));
        $db->connection()->pdo()->exec(
            "CREATE TABLE users ("
            . "id INTEGER PRIMARY KEY AUTOINCREMENT, email TEXT UNIQUE, name TEXT, password_hash TEXT, "
            . "created_at TEXT, updated_at TEXT)"
        );
        $db->connection()->pdo()->exec(
            "CREATE TABLE password_reset_tokens ("
            . "id INTEGER PRIMARY KEY AUTOINCREMENT, login TEXT, token_hash TEXT, expires_at TEXT, created_at TEXT)"
        );
        Model::useDb($db);

        $passwords = new NativePasswordHasher();
        $identities = new AppIdentityProvider($passwords);
        $jobs = new PublishedAccountsJobDispatcher();
        $application = new PublishedAccountsApplication();
        $manager = new AccountManager(
            $identities,
            $passwords,
            $jobs,
            new AccountsRoutes(
                "/account/login",
                "/account/logout",
                "/account/register",
                "/account/forgot-password",
                "/account/reset-password",
                "/account/resources/accounts.css"
            ),
            $application
        );

        $identity = $manager->register(new RegisterAccount(
            " EDIN@EXAMPLE.COM ",
            "old-password",
            ["name" => "Edin"]
        ));

        $this->assertInstanceOf(User::class, $identity);
        $this->assertSame("edin@example.com", $identity->email);
        $this->assertTrue($identities->validateCredentials($identity, "old-password"));
        $this->assertNull($manager->register(new RegisterAccount("edin@example.com", "duplicate")));

        $manager->requestPasswordReset("edin@example.com");
        $this->assertInstanceOf(SendPasswordResetJob::class, $jobs->job);
        $url = (string) ($jobs->job?->payload()["resetUrl"] ?? "");
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        $this->assertStringStartsWith("https://example.test/account/reset-password?", $url);
        $this->assertFalse($manager->resetPassword("edin@example.com", "wrong-token", "new-password"));
        $this->assertTrue($manager->resetPassword(
            "edin@example.com",
            (string) ($query["token"] ?? ""),
            "new-password"
        ));
        $updatedIdentity = $identities->findByLogin("edin@example.com");
        $this->assertInstanceOf(User::class, $updatedIdentity);
        $this->assertTrue($identities->validateCredentials($updatedIdentity, "new-password"));
        $this->assertSame(0, PasswordResetToken::count());
        $this->assertFalse($manager->resetPassword(
            "edin@example.com",
            (string) ($query["token"] ?? ""),
            "another-password"
        ));
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($directory);
    }
}

final class PublishedAccountsJobDispatcher implements JobDispatcherInterface
{
    public ?JobInterface $job = null;

    public function dispatch(JobInterface $job, int $delay = 0, ?string $queue = null): string
    {
        $this->job = $job;
        return "published-accounts-job";
    }
}

final class PublishedAccountsApplication extends Application
{
    protected string $baseUrl = "https://example.test";
}
