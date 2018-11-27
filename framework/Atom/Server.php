<?php

namespace Atom;

class Server
{
    private $vars = [];

    public function __construct($vars)
    {
        $this->vars = $vars;
    }

    public function getRequestMethod()
    {
        return $this->vars['REQUEST_METHOD'];
    }

    public function getRequestUri()
    {
        return $this->vars['REQUEST_URI'];
    }

    public function getScriptName()
    {
        return $this->vars['SCRIPT_NAME'];
    }

    public function getBasePath()
    {
        $path = $this->getScriptName();
        $dir = \pathinfo($path, PATHINFO_DIRNAME);
        return $dir;
    }

    public function getUri()
    {
        $httpMethod = $this->getRequestMethod();
        $uri = $this->getRequestUri();
        $basePath = $this->getBasePath();

        if (false !== $pos = strpos($uri, '?')) {
            $uri = substr($uri, 0, $pos);
        }

        $size = strlen($basePath);
        $uri = substr($uri, $size);

        $uri = rawurldecode($uri);
        return $uri;
    }
}
