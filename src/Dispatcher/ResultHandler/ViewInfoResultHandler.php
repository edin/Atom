<?php

declare(strict_types=1);

namespace Atom\Dispatcher\ResultHandler;

use Atom\Interfaces\IResultHandler;
use Psr\Http\Message\ResponseInterface;

class ViewInfoResultHandler implements IResultHandler
{
    use ResultHandlerTrait;

    public function isMatch(/*any*/$result): bool
    {
        return $result instanceof \Atom\Interfaces\IViewInfo;
    }

    public function process($result): ResponseInterface
    {
        $view = $this->getContainer()->View;
        $content = $view->render($result);

        $response = $this->getResponse();
        $response->getBody()->write($content);
        return $response;
    }
}
