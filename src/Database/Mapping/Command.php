<?php

class Command
{
    /**
     * @var string
     */
    public $query;

    /**
     * @var FieldMapping[]
     */
    public $parameters;

    public function __construct(string $query, array $parameters)
    {
        $this->query = $query;
        $this->parameters = $parameters;
    }
}
