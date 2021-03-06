<?php

declare(strict_types=1);

namespace Atom\Database\Query;

use Atom\Database\EntityCollection;
use Atom\Database\Interfaces\IConnection;
use Atom\Hydrator\IHydrator;

final class Command
{
    private ?IConnection $connection;
    private array $parameters = [];
    private string $sql = "";
    private ?IHydrator $hydrator = null;

    private function add(Parameter $parameter): void
    {
        $this->parameters[$parameter->getName()] = $parameter;
    }

    public function addParameter(string $name, $value, ?int $type = null, int $direction = Parameter::Input): void
    {
        $this->add(new Parameter($name, $value, $type, $direction));
    }

    public function setParameterValue(string $name, $value): void
    {
        $this->parameters[$name]->setValue($value);
    }

    /** @return mixed */
    public function getParameterValue(string $name)
    {
        return $this->parameters[$name]->getValue();
    }

    /** @return Parameter[] */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function setParameters(array $parameters): void
    {
        $this->parameters = $parameters;
    }

    public function setSql(string $sql): void
    {
        $this->sql = $sql;
    }

    public function getSql(): string
    {
        return $this->sql;
    }

    public function setConnection(IConnection $connection): void
    {
        $this->connection = $connection;
    }

    public function getConnection(): IConnection
    {
        return $this->connection;
    }

    public function setHydrator(?IHydrator $hydrator): void
    {
        $this->hydrator = $hydrator;
    }

    public function getLastInsertId()
    {
        return $this->connection->lastInsertId();
    }

    public function execute(): bool
    {
        return $this->connection->execute($this->sql, $this->parameters);
    }

    public function queryScalar()
    {
        return $this->connection->queryScalar($this->sql, $this->parameters);
    }

    public function queryAll(): array
    {
        $result = $this->connection->queryAll($this->sql, $this->parameters);
        return $result;
    }

    public function findAll(): EntityCollection
    {
        $items =  $this->queryAll();
        $result = [];

        foreach ($items as $item) {
            $result[] = $this->hydrator->hydrate($item);
        }

        return EntityCollection::from($result);
    }
}
