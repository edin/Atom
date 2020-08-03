# Service providers

[Atom Framework](Index.md)


Service provider is class with following where:
* class constructor is used to register new dependencies
* method **configure** is used to configure dependencies


```php
class ServiceProvider {
    public function __construct(Container $container, ...) {
        //Register services
    }
    public function configure(...) {
        //Configure services
    }
}
```

## Example

* Service provider that registers IConnection and IDatabaseConnector interfaces

```php
<?php

namespace App;

use Atom\Container\Container;
use Atom\Database\Connection;
use Atom\Database\Interfaces\IConnection;
use Atom\Database\Connector\MySqlConnector;
use Atom\Database\Interfaces\IDatabaseConnector;

class DatabaseServices 
{
    public function __construct(Container $container)
    {
        $container->bind(IConnection::class)
                  ->to(Connection::class);
                  
        $container->bind(IDatabaseConnector::class)
                  ->toFactory(function () {
                        return new MySqlConnector(
                            "localhost", 
                            "root", 
                            "root", 
                            "orm_test"
                    );
        });
    }
}
```

* Configuring router

```php
<?php

namespace App;

class Routes
{
    public function configure(Router $router) {
        // Configure router here
        $router->get("/", HomeConroller::class, "index");
        $router->get("/about", HomeConroller::class, "about");
    }
}
```

* Using service providers whitin application 

```php
<?php

namespace App;

class Application extends \Atom\Application
{
    public function configure()
    {
        $this->use(DatabaseServices::class);
        $this->use(Routes::class);
    }
}
```