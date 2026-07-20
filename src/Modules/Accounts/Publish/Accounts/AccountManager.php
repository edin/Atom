<?php

declare(strict_types=1);

namespace App\Accounts;

use App\Models\PasswordResetToken;
use App\Models\User;
use Atom\Application;
use Atom\Identity\IdentityInterface;
use Atom\Identity\IdentityProviderInterface;
use Atom\Identity\PasswordHasherInterface;
use Atom\Modules\Accounts\AccountManagerInterface;
use Atom\Modules\Accounts\AccountsRoutes;
use Atom\Modules\Accounts\Jobs\SendPasswordResetJob;
use Atom\Modules\Accounts\RegisterAccount;
use Atom\Queue\JobDispatcherInterface;
use DateTimeImmutable;
use SensitiveParameter;

final readonly class AccountManager implements AccountManagerInterface
{
    public function __construct(
        private IdentityProviderInterface $identities,
        private PasswordHasherInterface $passwords,
        private JobDispatcherInterface $jobs,
        private AccountsRoutes $routes,
        private Application $application
    ) {
    }

    public function register(RegisterAccount $account): ?IdentityInterface
    {
        $login = $this->normalize($account->login);
        if ($login === "" || $this->identities->findByLogin($login) !== null) {
            return null;
        }

        $user = new User();
        $user->email = $login;
        $user->name = trim($account->string("name"));
        $user->passwordHash = $this->passwords->hash($account->password());
        $user->save();

        return $user;
    }

    public function requestPasswordReset(string $login): void
    {
        $identity = $this->identities->findByLogin($login);
        if (!$identity instanceof User) {
            return;
        }

        $login = $this->normalize($identity->email);
        $this->deleteTokens($login);

        $token = bin2hex(random_bytes(32));
        $reset = new PasswordResetToken();
        $reset->login = $login;
        $reset->tokenHash = hash("sha256", $token);
        $reset->expiresAt = new DateTimeImmutable("+60 minutes");
        $reset->save();

        $url = rtrim($this->application->getBaseUrl(), "/")
            . $this->routes->resetPassword
            . "?"
            . http_build_query([
                "login" => $login,
                "token" => $token,
            ], encoding_type: PHP_QUERY_RFC3986);

        $this->jobs->dispatch(new SendPasswordResetJob($identity->email, $url, $identity->name));
    }

    public function resetPassword(
        string $login,
        string $token,
        #[SensitiveParameter] string $password
    ): bool {
        $login = $this->normalize($login);
        $identity = $this->identities->findByLogin($login);
        if (!$identity instanceof User) {
            return false;
        }

        $reset = PasswordResetToken::query()
            ->where("login", $login)
            ->where("token_hash", hash("sha256", $token))
            ->first();

        if (!$reset instanceof PasswordResetToken || $reset->expiresAt <= new DateTimeImmutable()) {
            if ($reset instanceof PasswordResetToken) {
                $reset->delete();
            }
            return false;
        }

        $identity->passwordHash = $this->passwords->hash($password);
        $identity->save();
        $this->deleteTokens($login);

        return true;
    }

    private function deleteTokens(string $login): void
    {
        foreach (PasswordResetToken::query()->where("login", $login)->all() as $token) {
            if ($token instanceof PasswordResetToken) {
                $token->delete();
            }
        }
    }

    private function normalize(string $login): string
    {
        return strtolower(trim($login));
    }
}
