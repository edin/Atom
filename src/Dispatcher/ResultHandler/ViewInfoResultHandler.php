<?php

declare(strict_types=1);

namespace Atom\Dispatcher\ResultHandler;

use Atom\Di\Injector;
use Atom\Dispatcher\ResultHandlerInterface;
use Atom\Http\Response;
use Atom\Interfaces\IViewInfo;
use Atom\View\View;

class ViewInfoResultHandler extends AbstractResultHandler implements ResultHandlerInterface
{
    public function __construct(Response $response, private Injector $injector)
    {
        parent::__construct($response);
    }

    public function isMatch(mixed $result): bool
    {
        return $result instanceof IViewInfo;
    }

    public function process(mixed $result): Response
    {
        $view = $this->injector->get(View::class);
        $content = $view->render($result);

        $response = $this->getResponse();
        $response->write($content);
        return $response;
    }
}
