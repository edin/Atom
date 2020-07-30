<?php

namespace Atom\Http;

class Response
{
    private int $status = 200;
    private array $headers = [];
    private $data;

    public function status(int $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function redirect(string $location, int $status): self
    {
        $this->header("Location", $location);
        $this->status($status);
        return $this;
    }

    public function json($data): self
    {
        $this->header("Content-Type", "application/json");
        $this->data = $data;
        return $this;
    }

    public function header(string $name, $value): self
    {
        if (empty($value)) {
            unset($this->headers[$value]);
        } else {
            $this->headers[$name] = $value;
        }
        return $this;
    }

    public function withHeaders(array $headers): self
    {
        foreach ($headers as $name => $value) {
            $this->header($name, $value);
        }
        return $this;
    }
}
