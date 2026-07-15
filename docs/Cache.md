# Cache

Atom provides a small file-backed cache for application data such as database query results, computed values, and future rate-limit counters. It does not cache templates or rendered pages automatically.

## Usage

Inject `CacheInterface` or access it from the application:

```php
use Atom\Cache\CacheInterface;

final readonly class UserRepository
{
    public function __construct(private CacheInterface $cache)
    {
    }

    public function active(): array
    {
        return $this->cache->remember(
            "users.active",
            300,
            fn(): array => $this->queryActiveUsers()
        );
    }
}
```

```php
$cache = $app->getCache();
$cache->set("key", $value, 60);
$value = $cache->get("key", $fallback);
$cache->delete("key");
```

Cached `null` and `false` values are distinguished from misses. Values must be serializable.

## Configuration

```env
CACHE_DIRECTORY=@root/storage/cache
CACHE_PREFIX=atom
CACHE_DEFAULT_TTL=0
```

TTL values are seconds. `0` means no expiration, and a negative TTL removes or declines to store the value. `DateInterval` instances are also accepted by the API. The prefix creates an isolated namespace; clearing one prefix does not clear another.

## Atomic operations

`remember()`, `add()`, and `increment()` hold an exclusive per-key file lock, making them safe across PHP workers:

```php
$acquired = $cache->add("job:import", true, 30);
$attempts = $cache->increment("login:{$clientIp}", 1, 60);
```

When incrementing an existing counter, its original expiration is preserved. This supports fixed-window rate limiting without extending the window on every request.

The file store uses hashed paths, atomic replacement, namespace-wide locking for `clear()`, and automatic removal of expired or corrupt entries. A database or distributed driver can implement `CacheInterface` later without changing application consumers.

## Maintenance commands

File entries are removed when an expired key is read. Entries that are never requested again can be cleaned explicitly:

```shell
php atom cache:prune
php atom cache:clear
```

`cache:prune` removes expired and corrupt entries from the active prefix and reports the number removed. `cache:clear` removes every entry in the active prefix without affecting other cache prefixes. Commands are registered automatically by the cache service provider.

Future cache drivers may optionally implement `PrunableCacheInterface`. The prune command exits with an error when the active driver does not support explicit pruning.
